<script setup>
import UiState from "@/components/UiState.vue";
defineProps({ forbidden: Boolean });
</script>
<template>
  <div class="container page-section">
    <UiState
      empty
      :title="forbidden ? 'Área restrita' : 'Página não encontrada'"
      :description="
        forbidden
          ? 'Esta área está disponível apenas para administradores.'
          : 'O endereço pode ter mudado. Continue explorando a loja.'
      "
      ><template #action
        ><RouterLink class="btn btn-primary" to="/"
          >Voltar ao catálogo</RouterLink
        ></template
      ></UiState
    >
  </div>
</template>
