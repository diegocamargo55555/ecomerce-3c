<script setup>
import { computed, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { login } from "@/api/auth/login";
import { register } from "@/api/auth/register";
import { resourceData } from "@/api/http";
import { session, addToCart, notify, clearCart } from "@/stores/shop";
import AppField from "@/components/AppField.vue";
import AppIcon from "@/components/AppIcon.vue";
const props = defineProps({ mode: String });
const route = useRoute();
const router = useRouter();
const registering = computed(() => props.mode === "register");
const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});
const busy = ref(false);
const error = ref("");
const errors = ref({});
async function submit() {
  busy.value = true;
  error.value = "";
  errors.value = {};
  try {
    const user = resourceData(
      await (registering.value
        ? register(form)
        : login({ email: form.email, password: form.password })),
    );
    clearCart();
    session.user = user;
    session.ready = true;
    session.error = "";
    let pending;
    try {
      pending = JSON.parse(sessionStorage.getItem("pendingCartItem"));
    } catch {
      /* Discard invalid browser state. */
    }
    sessionStorage.removeItem("pendingCartItem");
    if (
      Number.isInteger(pending?.productId) &&
      Number.isInteger(pending?.quantity) &&
      pending.quantity > 0
    ) {
      try {
        await addToCart(pending.productId, pending.quantity);
        notify("Produto adicionado ao carrinho.");
      } catch (failure) {
        notify(failure.message, "error");
        return router.replace(`/produtos/${pending.productId}`);
      }
    } else {
      notify(
        registering.value
          ? "Conta criada. Boas compras!"
          : "Que bom ter você por aqui!",
      );
    }
    const destination = route.query.redirect;
    await router.replace(
      typeof destination === "string" &&
        destination.startsWith("/") &&
        !destination.startsWith("//")
        ? destination
        : "/",
    );
  } catch (failure) {
    error.value = failure.message;
    errors.value = failure.errors || {};
  } finally {
    busy.value = false;
  }
}
</script>
<template>
  <div class="container auth-page">
    <section class="auth-story">
      <span class="hero-kicker">BEM-VINDO À LOJA 3C</span>
      <h1>Suas escolhas.<br />Seu jeito.</h1>
      <p>
        Entre para guardar seus favoritos no carrinho e acompanhar cada pedido.
      </p>
      <div class="auth-art" aria-hidden="true">
        <AppIcon name="bag" :size="120" /><span class="auth-art-dot" /><span
          class="art-star"
          >✳︎</span
        >
      </div>
      <RouterLink to="/" class="text-link"
        >Explorar o catálogo <AppIcon name="arrow" :size="18"
      /></RouterLink>
    </section>
    <section class="auth-card" :key="mode">
      <span class="eyebrow">VAMOS COMEÇAR</span>
      <h2>{{ registering ? "Crie sua conta" : "Bom te ver de novo" }}</h2>
      <p class="muted">
        {{
          registering
            ? "Preencha seus dados para fazer parte."
            : "Entre na sua conta para continuar."
        }}
      </p>
      <p v-if="route.query.redirect === '/carrinho'" class="alert alert-info">
        Entre para adicionar o produto ao carrinho.
      </p>
      <form @submit.prevent="submit">
        <p v-if="error" class="alert alert-error" role="alert">{{ error }}</p>
        <AppField
          v-if="registering"
          v-model="form.name"
          label="Nome completo"
          name="name"
          autocomplete="name"
          required
          maxlength="255"
          :error="errors.name"
        /><AppField
          v-model="form.email"
          label="E-mail"
          type="email"
          name="email"
          autocomplete="email"
          required
          maxlength="255"
          placeholder="voce@exemplo.com"
          :error="errors.email"
        /><AppField
          v-model="form.password"
          label="Senha"
          type="password"
          name="password"
          :autocomplete="registering ? 'new-password' : 'current-password'"
          required
          :minlength="registering ? 8 : undefined"
          :error="errors.password"
        />
        <p v-if="registering" class="field-hint">
          Use pelo menos 8 caracteres.
        </p>
        <AppField
          v-if="registering"
          v-model="form.password_confirmation"
          label="Confirme a senha"
          type="password"
          name="password_confirmation"
          autocomplete="new-password"
          required
          minlength="8"
          :error="errors.password_confirmation"
        /><button class="btn btn-primary full-width" :disabled="busy">
          {{
            busy
              ? "Aguarde..."
              : registering
                ? "Criar minha conta"
                : "Entrar na conta"
          }}<AppIcon v-if="!busy" name="arrow" :size="18" />
        </button>
      </form>
      <p class="auth-switch">
        {{ registering ? "Já tem uma conta?" : "Ainda não tem conta?" }}
        <RouterLink
          :to="{
            path: registering ? '/login' : '/cadastro',
            query: route.query,
          }"
          >{{ registering ? "Entrar" : "Cadastre-se" }}</RouterLink
        >
      </p>
    </section>
  </div>
</template>
