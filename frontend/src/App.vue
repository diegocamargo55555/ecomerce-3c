<script setup>
import { resources, navigation } from "./resources.js";
import { initials } from "./manipulacao/formatacao.js";
import AuthView from "./components/AuthView.vue";
import OverviewView from "./components/OverviewView.vue";
import ResourceView from "./components/ResourceView.vue";
import CatalogView from "./components/CatalogView.vue";
import Icon from "./components/Icon.vue";
import { useApp } from "./manipulacao/useApp.js";

const {
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
} = useApp();
</script>

<template>
  <div v-if="!publicPage && (starting || startError)" class="startup-screen">
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
  <AuthView
    v-else-if="page === 'login' && !user"
    :notice="notice"
    @authenticated="authenticated"
  />
  <CatalogView
    v-else-if="publicPage || customerPage"
    :section="productId ? 'product' : orderId ? 'order' : page"
    :product-id="productId"
    :order-id="orderId"
    :user="user"
    :notice="notice"
    :signing-out="signingOut"
    @navigate="navigate"
    @sign-out="signOut"
    @user-updated="user = $event"
  />
  <div v-else-if="isAdmin" class="app-layout">
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
      <a href="#products" class="brand" @click.prevent="navigate('products')"
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
            :href="`#${managementPage(key)}`"
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
        <div v-if="user" class="user-card">
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
          <a href="#products" class="text-button"
            ><Icon name="bag" :size="16" /> Ver loja</a
          >
          <span class="workspace-badge"><i /> Gestão</span
          ><span v-if="user" class="avatar small" :title="user.name">{{
            initials(user.name)
          }}</span>
        </div>
      </header>
      <main id="main-content" ref="main" tabindex="-1">
        <OverviewView
          v-if="page === 'overview'"
          :user="user"
          @navigate="navigateManagement"
        /><ResourceView
          v-else
          :resource-key="resourceKey"
          :user="user"
          @notify="notify"
          @navigate="navigateManagement"
          @user-updated="user = $event"
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
