<script setup>
import AppIcon from "./AppIcon.vue";
defineProps({
  loading: Boolean,
  error: String,
  empty: Boolean,
  title: String,
  description: String,
  icon: { type: String, default: "bag" },
});
defineEmits(["retry"]);
</script>
<template>
  <div v-if="loading" class="state-panel" role="status">
    <span class="spinner" />
    <p>Carregando...</p>
  </div>
  <div v-else-if="error" class="state-panel" role="alert">
    <span class="state-icon"><AppIcon name="box" :size="30" /></span>
    <h2>Algo não saiu como esperado</h2>
    <p>{{ error }}</p>
    <button class="btn btn-primary" @click="$emit('retry')">
      Tentar novamente
    </button>
  </div>
  <div v-else-if="empty" class="state-panel">
    <span class="state-icon"><AppIcon :name="icon" :size="32" /></span>
    <h2>{{ title || "Nada por aqui ainda" }}</h2>
    <p>{{ description }}</p>
    <slot name="action" />
  </div>
  <slot v-else />
</template>
