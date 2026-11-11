<script setup>
import Icon from "./Icon.vue";
import { useAuth } from "../manipulacao/useAuth.js";

defineProps({ notice: String });
const emit = defineEmits(["authenticated"]);
const {
  mode,
  busy,
  showPassword,
  error,
  errors,
  form,
  submit,
} = useAuth(emit);
</script>

<template>
  <main class="auth-layout">
    <section class="auth-story">
      <a class="brand" href="#"
        ><span class="brand-mark"><Icon name="omega" :size="23" /></span
        ><span
          >40K<small>ESCOLHAS PARA O SEU DIA</small></span
        ></a
      >
      <div class="auth-message">
        <span class="eyebrow">BEM-VINDO À SUA CONTA.</span>
        <h1>Que bom <br />ter você aqui.</h1>
        <p>
          Entre ou crie sua conta e conheça<br class="desktop-only" />
          os produtos da nossa loja.
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
        <span class="eyebrow">BEM-VINDO À 40K</span>
        <h2>
          {{ mode === "login" ? "Bom ter você por aqui." : "Vamos começar?" }}
        </h2>
        <p class="muted">
          {{
            mode === "login"
              ? "Entre na sua conta para continuar."
              : "Crie sua conta para acessar a loja."
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
                  ? "Entrar na minha conta"
                  : "Criar minha conta"
            }}</span
            ><Icon v-if="!busy" name="right" :size="18" />
          </button>
        </form>
        <p class="auth-caption">
          <Icon name="bag" :size="15" /> Seus próximos favoritos estão por aqui.
        </p>
      </div>
      <span class="auth-bottom"
        >40K <span>·</span> Simples por natureza.</span
      >
    </section>
  </main>
</template>
