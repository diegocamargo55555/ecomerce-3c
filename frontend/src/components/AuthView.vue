<script setup>
import { reactive, ref, watch } from "vue";
import { login } from "../api/auth/login.js";
import { register } from "../api/auth/register.js";
import { resourceData } from "../api/http.js";
import Icon from "./Icon.vue";

defineProps({ notice: String });
const emit = defineEmits(["authenticated"]);
const mode = ref("login");
const busy = ref(false);
const showPassword = ref(false);
const error = ref("");
const errors = ref({});
const form = reactive({
  name: "",
  email: "",
  password: "",
  password_confirmation: "",
});
watch(mode, () => {
  error.value = "";
  errors.value = {};
  form.password = "";
  form.password_confirmation = "";
});

async function submit() {
  busy.value = true;
  error.value = "";
  errors.value = {};
  try {
    const payload =
      mode.value === "login"
        ? { email: form.email.trim(), password: form.password }
        : { ...form, name: form.name.trim(), email: form.email.trim() };
    const user = resourceData(
      await (mode.value === "login" ? login(payload) : register(payload)),
    );
    form.password = "";
    form.password_confirmation = "";
    emit("authenticated", user);
  } catch (failure) {
    error.value = failure.message;
    errors.value = failure.errors || {};
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <main class="auth-layout">
    <section class="auth-story">
      <a class="brand" href="#"
        ><span class="brand-mark"><Icon name="bag" :size="23" /></span
        ><span
          >3c<span class="brand-light">commerce</span
          ><small>GESTÃO DA SUA LOJA</small></span
        ></a
      >
      <div class="auth-message">
        <span class="eyebrow">MENOS COMPLICAÇÃO. MAIS CONTROLE.</span>
        <h1>Sua loja,<br />em ordem.</h1>
        <p>
          Um espaço simples para cuidar dos produtos,<br class="desktop-only" />
          dos pedidos e de quem compra de você.
        </p>
        <div class="auth-illustration" aria-hidden="true">
          <div class="illustration-card">
            <span class="illustration-icon"
              ><Icon name="box" :size="30"
            /></span>
            <div>
              <span class="illustration-line long"></span
              ><span class="illustration-line"></span>
            </div>
            <span class="illustration-check"
              ><Icon name="check" :size="18"
            /></span>
          </div>
          <div class="illustration-card second">
            <span class="illustration-icon"
              ><Icon name="orders" :size="30"
            /></span>
            <div>
              <span class="illustration-line long"></span
              ><span class="illustration-line"></span>
            </div>
            <span class="illustration-check"
              ><Icon name="check" :size="18"
            /></span>
          </div>
        </div>
      </div>
      <a href="#products" class="auth-footnote">Voltar para a loja →</a>
    </section>
    <section class="auth-main">
      <div class="auth-form-wrap">
        <span class="eyebrow">BEM-VINDO À 3C COMMERCE</span>
        <h2>
          {{ mode === "login" ? "Bom ter você por aqui." : "Vamos começar?" }}
        </h2>
        <p class="muted">
          {{
            mode === "login"
              ? "Entre na sua conta para cuidar da sua loja."
              : "Crie sua conta e organize sua operação."
          }}
        </p>
        <div class="auth-tabs" aria-label="Acesso">
          <button
            :class="{ active: mode === 'login' }"
            :disabled="busy"
            @click="mode = 'login'"
          >
            Entrar</button
          ><button
            :class="{ active: mode === 'register' }"
            :disabled="busy"
            @click="mode = 'register'"
          >
            Criar conta
          </button>
        </div>
        <div v-if="notice && !error" class="notice" role="status">
          {{ notice }}
        </div>
        <div v-if="error" class="alert" role="alert">
          <Icon name="alert" :size="18" /><span>{{ error }}</span>
        </div>
        <form class="auth-form" @submit.prevent="submit">
          <div v-if="mode === 'register'" class="field">
            <label for="auth-name">Nome completo</label
            ><input
              id="auth-name"
              v-model="form.name"
              autocomplete="name"
              required
              maxlength="255"
              placeholder="Como podemos chamar você?"
              :disabled="busy"
              :aria-invalid="!!errors.name"
              :aria-describedby="errors.name ? 'error-name' : undefined"
            /><span v-if="errors.name" id="error-name" class="field-error">{{
              errors.name[0]
            }}</span>
          </div>
          <div class="field">
            <label for="auth-email">E-mail</label
            ><input
              id="auth-email"
              v-model="form.email"
              type="email"
              autocomplete="username"
              required
              maxlength="255"
              placeholder="voce@exemplo.com"
              :disabled="busy"
              :aria-invalid="!!errors.email"
              :aria-describedby="errors.email ? 'error-email' : undefined"
            /><span v-if="errors.email" id="error-email" class="field-error">{{
              errors.email[0]
            }}</span>
          </div>
          <div class="field">
            <label for="auth-password">Senha</label>
            <div class="password-input">
              <input
                id="auth-password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                :autocomplete="
                  mode === 'login' ? 'current-password' : 'new-password'
                "
                required
                :minlength="mode === 'register' ? 8 : undefined"
                placeholder="Sua senha"
                :disabled="busy"
                :aria-invalid="!!errors.password"
                :aria-describedby="
                  errors.password ? 'error-password' : undefined
                "
              /><button
                type="button"
                class="icon-button"
                :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
              >
                <Icon name="eye" :size="18" />
              </button>
            </div>
            <span
              v-if="errors.password"
              id="error-password"
              class="field-error"
              >{{ errors.password[0] }}</span
            ><small v-else-if="mode === 'register'" class="muted"
              >Use pelo menos 8 caracteres.</small
            >
          </div>
          <div v-if="mode === 'register'" class="field">
            <label for="auth-confirm">Confirmar senha</label
            ><input
              id="auth-confirm"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              minlength="8"
              placeholder="Repita sua senha"
              :disabled="busy"
            />
          </div>
          <button
            type="submit"
            class="button primary auth-submit"
            :disabled="busy"
          >
            <span v-if="busy" class="spinner" /><span>{{
              busy
                ? "Aguarde um instante…"
                : mode === "login"
                  ? "Entrar na minha loja"
                  : "Criar minha conta"
            }}</span
            ><Icon v-if="!busy" name="right" :size="18" />
          </button>
        </form>
        <p class="auth-caption">
          <Icon name="bag" :size="15" /> Sua operação começa com uma boa
          organização.
        </p>
      </div>
      <span class="auth-bottom"
        >3C Commerce <span>·</span> Simples por natureza.</span
      >
    </section>
  </main>
</template>
