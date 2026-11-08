<script setup>
import {
  computed,
  onMounted,
  onBeforeUnmount,
  ref,
  watch,
  nextTick,
} from "vue";
import { getCurrentUser } from "./api/auth/me.js";
import { logout } from "./api/auth/logout.js";
import { resources, navigation, initials } from "./resources.js";
import AuthView from "./components/AuthView.vue";
import OverviewView from "./components/OverviewView.vue";
import ResourceView from "./components/ResourceView.vue";
import Icon from "./components/Icon.vue";

const user = ref(null);
const starting = ref(true);
const startError = ref("");
const notice = ref("");
const page = ref("overview");
const menuOpen = ref(false);
const compactLayout = window.matchMedia("(max-width: 760px)");
const mobile = ref(compactLayout.matches);
const originalOverflow = document.body.style.overflow;
const signingOut = ref(false);
const toast = ref(null);
let toastTimer;
const title = computed(() => resources[page.value]?.title || "Visão geral");
const activeKey = computed(() => resources[page.value]?.parent || page.value);

function syncRoute() {
  const key = window.location.hash.slice(1);
  page.value = Object.hasOwn(resources, key) ? key : "overview";
  menuOpen.value = false;
  document.title = `${resources[page.value]?.title || "Visão geral"} · 3C Commerce`;
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
  }
}
function authenticated(value) {
  user.value = value;
  notice.value = "";
  navigate("overview");
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
</script>

<template>
  <div v-if="starting || startError" class="startup-screen">
    <span class="brand-mark"><Icon name="bag" :size="26" /></span
    ><template v-if="starting"
      ><span class="spinner" />
      <p role="status">Preparando sua loja…</p></template
    ><template v-else
      ><h1>Vamos tentar de novo?</h1>
      <p role="alert">{{ startError }}</p>
      <button class="button primary" @click="start">
        <Icon name="refresh" :size="17" /> Tentar novamente
      </button></template
    >
  </div>
  <AuthView v-else-if="!user" :notice="notice" @authenticated="authenticated" />
  <div v-else class="app-layout">
    <a
      class="skip-link"
      href="#main-content"
      @click.prevent="$refs.main.focus()"
      >Pular para o conteúdo</a
    >
    <button
      v-if="menuOpen"
      class="sidebar-backdrop"
      aria-label="Fechar menu"
      @click="menuOpen = false"
    />
    <aside
      id="main-navigation"
      :class="['sidebar', { open: menuOpen }]"
      :inert="mobile && !menuOpen"
      aria-label="Navegação principal"
    >
      <a href="#overview" class="brand" @click.prevent="navigate('overview')"
        ><span class="brand-mark"><Icon name="bag" :size="23" /></span
        ><span
          >3c<span class="brand-light">commerce</span
          ><small>GESTÃO DA SUA LOJA</small></span
        ></a
      ><button
        class="icon-button sidebar-close"
        aria-label="Fechar menu"
        @click="menuOpen = false"
      >
        <Icon name="close" />
      </button>
      <div class="store-selector">
        <span class="store-avatar">3C</span>
        <div><strong>Minha loja</strong><span>Seu espaço de gestão</span></div>
        <span class="store-status" title="Sessão ativa"></span>
      </div>
      <nav>
        <div v-for="group in navigation" :key="group.label" class="nav-group">
          <span class="nav-group-label">{{ group.label }}</span
          ><a
            v-for="key in group.items"
            :key="key"
            :href="`#${key}`"
            :class="['nav-item', { active: activeKey === key }]"
            :aria-current="activeKey === key ? 'page' : undefined"
            @click="menuOpen = false"
            ><Icon :name="resources[key]?.icon || 'grid'" :size="19" /><span>{{
              resources[key]?.title || "Visão geral"
            }}</span
            ><i v-if="activeKey === key"
          /></a>
        </div>
      </nav>
      <div class="sidebar-bottom">
        <div class="sidebar-note">
          <Icon name="bag" :size="19" /><span
            >Pequenos detalhes.<br /><strong>Uma loja melhor.</strong></span
          >
        </div>
        <div class="user-card">
          <span class="avatar">{{ initials(user.name) }}</span>
          <div>
            <strong>{{ user.name }}</strong
            ><small>{{ user.email }}</small>
          </div>
          <button
            class="icon-button"
            :aria-label="signingOut ? 'Saindo da conta' : 'Sair da conta'"
            title="Sair"
            :disabled="signingOut"
            @click="signOut"
          >
            <span v-if="signingOut" class="spinner" /><Icon
              v-else
              name="logout"
              :size="18"
            />
          </button>
        </div>
      </div>
    </aside>
    <div class="workspace" :inert="mobile && menuOpen">
      <header class="topbar">
        <div class="breadcrumbs">
          <button
            class="icon-button menu-toggle"
            aria-label="Abrir menu"
            aria-controls="main-navigation"
            :aria-expanded="menuOpen"
            @click="menuOpen = !menuOpen"
          >
            <Icon name="menu" /></button
          ><span class="breadcrumb-home">Minha loja</span
          ><Icon name="right" :size="13" /><strong>{{ title }}</strong>
        </div>
        <div class="topbar-right">
          <span class="workspace-badge"><i /> Espaço de gestão</span
          ><span class="avatar small" :title="user.name">{{
            initials(user.name)
          }}</span>
        </div>
      </header>
      <main id="main-content" ref="main" tabindex="-1">
        <OverviewView
          v-if="page === 'overview'"
          :user="user"
          @navigate="navigate"
        /><ResourceView
          v-else
          :resource-key="page"
          :user="user"
          @notify="notify"
          @navigate="navigate"
          @user-updated="user = $event"
          @self-deleted="endSession('Sua conta foi excluída.')"
        />
      </main>
      <footer class="workspace-footer">
        <span>3c commerce</span><span>O essencial para a sua loja.</span>
      </footer>
    </div>
  </div>
  <div
    v-if="toast"
    :class="['toast', toast.type]"
    :role="toast.type === 'error' ? 'alert' : 'status'"
  >
    <Icon
      :name="toast.type === 'error' ? 'alert' : 'check'"
      :size="18"
    /><span>{{ toast.message }}</span
    ><button
      class="icon-button"
      aria-label="Fechar mensagem"
      @click="toast = null"
    >
      <Icon name="close" :size="16" />
    </button>
  </div>
</template>
