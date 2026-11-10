import {
  computed,
  onMounted,
  onBeforeUnmount,
  ref,
  watch,
  nextTick,
} from "vue";
import { getCurrentUser } from "../requisicoes/auth/me.js";
import { logout } from "../requisicoes/auth/logout.js";
import { resources } from "../resources.js";

import { isKnownRoute } from "../validacoes/rotas.js";

export function useApp() {
  const user = ref(null);
  const starting = ref(true);
  const startError = ref("");
  const notice = ref("");
  const page = ref("products");
  const productId = computed(() =>
    page.value.startsWith("products/") ? page.value.slice(9) : "",
  );
  const orderId = computed(() => page.value.startsWith("order/") ? page.value.slice(6) : "");
  const customerPage = computed(() => ["checkout", "my-orders", "profile"].includes(page.value) || !!orderId.value);
  const afterLogin = ref("cart");
  const menuOpen = ref(false);
  const compactLayout = window.matchMedia("(max-width: 760px)");
  const mobile = ref(compactLayout.matches);
  const originalOverflow = document.body.style.overflow;
  const signingOut = ref(false);
  const toast = ref(null);
  let toastTimer;
  const resourceKey = computed(() => page.value.replace(/^manage-/, ""));
  const title = computed(
    () =>
      page.value === "login"
        ? "Sua conta"
        : page.value === "cart" ? "Meu carrinho"
        : page.value === "checkout" ? "Finalizar compra"
        : page.value === "my-orders" ? "Meus pedidos"
        : page.value === "profile" ? "Meu perfil"
        : orderId.value ? `Pedido #${orderId.value}`
        : productId.value ? "Produto" : resources[resourceKey.value]?.title || "Visão geral",
  );
  const activeKey = computed(
    () => resources[resourceKey.value]?.parent || resourceKey.value,
  );
  const publicPage = computed(() =>
    ["products", "categories", "cart"].includes(page.value) || !!productId.value,
  );
  const isAdmin = computed(() => user.value?.role === "admin");

  function managementPage(key) {
    return ["products", "categories"].includes(key) ? `manage-${key}` : key;
  }
  function navigateManagement(key) {
    navigate(managementPage(key));
  }

  function syncRoute() {
    const key = window.location.hash.slice(1);
    page.value = isKnownRoute(key, resources) ? key : "products";
    if (!starting.value && !startError.value) {
      if (page.value === "login" && user.value) {
        navigate(isAdmin.value ? "overview" : "products");
        return;
      }
      if (customerPage.value && !user.value) {
        afterLogin.value = page.value;
        notice.value = "Entre na sua conta para continuar.";
        navigate("login");
        return;
      }
      if (!publicPage.value && !customerPage.value && page.value !== "login" && !isAdmin.value) {
        notice.value = user.value
          ? "O painel é exclusivo de administradores. Você pode navegar pelo catálogo."
          : "Entre na sua conta para continuar.";
        navigate(user.value ? "products" : "login");
        return;
      }
    }
    menuOpen.value = false;
    document.title = `${title.value} · 3C Commerce`;
  }
  function navigate(key) {
    window.location.hash = key;
    syncRoute();
  }
  function notify(message, type = "success") {
    clearTimeout(toastTimer);
    toast.value = { message, type };
    toastTimer = setTimeout(() => {
      toast.value = null;
    }, 5000);
  }
  function endSession(
    message = "Sua sessão terminou. Entre novamente para continuar.",
  ) {
    user.value = null;
    notice.value = message;
    menuOpen.value = false;
    toast.value = null;
    navigate("products");
  }
  function expired() {
    endSession();
  }
  function syncViewport(event) {
    mobile.value = event.matches;
    if (!event.matches) menuOpen.value = false;
  }
  function closeMenu(event) {
    if (event.key === "Escape" && menuOpen.value) {
      menuOpen.value = false;
      nextTick(() => document.querySelector(".menu-toggle")?.focus());
    }
  }
  watch([menuOpen, mobile], ([open, compact]) => {
    document.body.style.overflow = open && compact ? "hidden" : originalOverflow;
    if (open && compact)
      nextTick(() => document.querySelector(".sidebar-close")?.focus());
  });
  async function start() {
    starting.value = true;
    startError.value = "";
    try {
      user.value = await getCurrentUser();
    } catch (failure) {
      if (failure.status !== 401) startError.value = failure.message;
    } finally {
      starting.value = false;
      syncRoute();
    }
  }
  function authenticated(value, warnings = []) {
    user.value = value;
    notice.value = warnings.join(" ");
    if (isAdmin.value && warnings.length) notify(notice.value, "warning");
    const destination = afterLogin.value;
    afterLogin.value = "cart";
    navigate(destination !== "cart" ? destination : isAdmin.value ? "overview" : "cart");
  }
  async function signOut() {
    signingOut.value = true;
    try {
      await logout();
      endSession("Você saiu da sua conta. Até a próxima!");
    } catch (failure) {
      notify(failure.message, "error");
    } finally {
      signingOut.value = false;
    }
  }
  onMounted(() => {
    syncRoute();
    start();
    window.addEventListener("hashchange", syncRoute);
    window.addEventListener("session-expired", expired);
    window.addEventListener("keydown", closeMenu);
    compactLayout.addEventListener("change", syncViewport);
  });
  onBeforeUnmount(() => {
    window.removeEventListener("hashchange", syncRoute);
    window.removeEventListener("session-expired", expired);
    window.removeEventListener("keydown", closeMenu);
    compactLayout.removeEventListener("change", syncViewport);
    document.body.style.overflow = originalOverflow;
    clearTimeout(toastTimer);
  });

  return {
    user,
    starting,
    startError,
    notice,
    page,
    productId,
    orderId,
    customerPage,
    menuOpen,
    mobile,
    signingOut,
    toast,
    resourceKey,
    title,
    activeKey,
    publicPage,
    isAdmin,
    managementPage,
    navigateManagement,
    navigate,
    notify,
    start,
    authenticated,
    signOut,
  };
}
