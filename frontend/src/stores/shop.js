import { computed, reactive, ref } from "vue";
import { apiData } from "@/api/http";
import { getCurrentUser } from "@/api/auth/me";

export const session = reactive({ user: null, ready: false, error: "" });
export const cart = reactive({ id: null, items: [], busy: false });
export const notice = ref(null);
let noticeTimer;
export function notify(message, kind = "success") {
  notice.value = { message, kind };
  clearTimeout(noticeTimer);
  noticeTimer = setTimeout(() => {
    notice.value = null;
  }, 6000);
}

export function clearCart() {
  cart.id = null;
  cart.items = [];
  cart.busy = false;
}
let restoring;
export async function restoreSession() {
  if (session.ready) return;
  if (!restoring)
    restoring = (async () => {
      try {
        session.user = await getCurrentUser();
        session.error = "";
      } catch (error) {
        session.user = null;
        session.error = error.status === 401 ? "" : error.message;
      } finally {
        session.ready = true;
        restoring = null;
      }
    })();
  return restoring;
}

window.addEventListener("auth:expired", () => {
  session.user = null;
  clearCart();
});

export async function loadCart() {
  if (!session.user) {
    clearCart();
    return;
  }
  const userId = session.user.id;
  const result = await apiData("/api/cart");
  if (session.user?.id === userId) {
    cart.id = result.id;
    cart.items = result.items || [];
  }
}
export const cartCount = computed(() =>
  cart.items.reduce((sum, item) => sum + item.quantity, 0),
);
export const cartTotal = computed(
  () =>
    cart.items.reduce(
      (sum, item) =>
        sum +
        Math.round(Number(item.product?.price || 0) * 100) * item.quantity,
      0,
    ) / 100,
);
export const invalidCart = computed(() =>
  cart.items.some(
    (item) =>
      !item.product ||
      !item.product.is_active ||
      item.quantity > item.product.stock,
  ),
);

export async function addToCart(productId, quantity = 1) {
  if (cart.busy) return false;
  cart.busy = true;
  try {
    if (!cart.id) await loadCart();
    await apiData("/api/cart-items", {
      method: "POST",
      body: { cart_id: cart.id, product_id: productId, quantity },
    });
    await loadCart();
    return true;
  } finally {
    cart.busy = false;
  }
}
export async function changeItem(item, quantity) {
  cart.busy = true;
  try {
    await apiData(`/api/cart-items/${item.id}`, {
      method: quantity ? "PATCH" : "DELETE",
      ...(quantity ? { body: { quantity } } : {}),
    });
    await loadCart();
  } finally {
    cart.busy = false;
  }
}
