<script setup>
import { ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { logout } from "@/api/auth/logout";
import {
  session,
  cartCount,
  clearCart,
  loadCart,
  notify,
  notice,
} from "@/stores/shop";
import AppIcon from "@/components/AppIcon.vue";
const route = useRoute();
const router = useRouter();
const search = ref("");
const leaving = ref(false);
watch(
  () => route.query.q,
  (value) => {
    search.value = typeof value === "string" ? value : "";
  },
  { immediate: true },
);
watch(
  () => session.user?.id,
  async (id) => {
    if (!id) return clearCart();
    try {
      await loadCart();
    } catch (error) {
      notify(error.message, "error");
    }
  },
  { immediate: true },
);
function find() {
  router.push({
    path: "/",
    query: {
      ...(route.path === "/" ? route.query : {}),
      q: search.value.trim() || undefined,
    },
  });
}
async function signOut() {
  leaving.value = true;
  try {
    await logout();
    session.user = null;
    clearCart();
    sessionStorage.removeItem("pendingCartItem");
    notify("Você saiu da sua conta.");
    await router.push("/");
  } catch (error) {
    notify(error.message, "error");
  } finally {
    leaving.value = false;
  }
}
</script>
<template>
  <a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
  <div class="utility-bar">
    <div class="container">
      <span
        ><AppIcon name="truck" :size="14" /> Frete grátis para suas
        escolhas</span
      ><span>Informática, eletrônicos e muito mais</span>
    </div>
  </div>
  <header class="site-header">
    <div class="container header-main">
      <RouterLink to="/" class="brand" aria-label="Loja 3C, início"
        ><span class="brand-symbol"><AppIcon name="bag" :size="24" /></span
        ><span
          >loja<span class="brand-accent">3c</span
          ><small>ESCOLHAS PARA O SEU DIA</small></span
        ></RouterLink
      >
      <form class="header-search" role="search" @submit.prevent="find">
        <label class="sr-only" for="site-search">Buscar produtos</label
        ><AppIcon name="search" :size="19" /><input
          id="site-search"
          v-model="search"
          placeholder="O que você está procurando?"
          type="search"
        /><button type="submit" aria-label="Buscar produtos">
          <AppIcon name="arrow" :size="19" />
        </button>
      </form>
      <div class="header-actions">
        <details v-if="session.user" class="account-menu">
          <summary>
            <AppIcon name="user" /><span
              >Olá, {{ session.user.name.split(" ")[0] }}</span
            >
          </summary>
          <div class="account-popover">
            <RouterLink
              to="/pedidos"
              @click="$event.target.closest('details').removeAttribute('open')"
              >Meus pedidos</RouterLink
            ><RouterLink
              v-if="session.user.is_admin"
              to="/admin"
              @click="$event.target.closest('details').removeAttribute('open')"
              >Administrar loja</RouterLink
            ><button :disabled="leaving" @click="signOut">
              {{ leaving ? "Saindo..." : "Sair da conta" }}
            </button>
          </div>
        </details>
        <RouterLink v-else to="/login" class="account-link"
          ><AppIcon name="user" /><span>Entrar</span></RouterLink
        ><RouterLink
          to="/carrinho"
          class="cart-link"
          :aria-label="`Carrinho com ${cartCount} produtos`"
          ><AppIcon name="cart" :size="23" /><span class="cart-count">{{
            cartCount
          }}</span></RouterLink
        >
      </div>
    </div>
  </header>
  <main id="conteudo" tabindex="-1">
    <div v-if="session.error" class="container">
      <p class="alert alert-error" role="alert">{{ session.error }}</p>
    </div>
    <RouterView />
  </main>
  <footer class="site-footer">
    <div class="container">
      <RouterLink class="brand footer-brand" to="/"
        >loja<span class="brand-accent">3c</span></RouterLink
      >
      <p>Escolhas simples para o seu dia a dia.</p>
      <span>Projeto 3C · {{ new Date().getFullYear() }}</span>
    </div>
  </footer>
  <Transition name="toast"
    ><div
      v-if="notice"
      class="toast-message"
      :class="{ 'toast-error': notice.kind === 'error' }"
      :role="notice.kind === 'error' ? 'alert' : 'status'"
    >
      <AppIcon
        :name="notice.kind === 'error' ? 'close' : 'check'"
        :size="20"
      /><span>{{ notice.message }}</span
      ><button aria-label="Fechar mensagem" @click="notice = null">
        <AppIcon name="close" :size="17" />
      </button></div
  ></Transition>
</template>
