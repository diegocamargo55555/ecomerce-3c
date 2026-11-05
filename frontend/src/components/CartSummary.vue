<script setup>
import { money } from "@/utils/format";
import AppIcon from "./AppIcon.vue";
defineProps({ total: Number, count: Number });
</script>
<template>
  <aside class="summary-card">
    <h2>Resumo do pedido</h2>
    <div class="summary-row">
      <span>Produtos ({{ count }})</span><span>{{ money(total) }}</span>
    </div>
    <div class="summary-row">
      <span>Entrega</span><span class="text-green">Grátis</span>
    </div>
    <div class="summary-row summary-total">
      <strong>Total</strong><strong>{{ money(total) }}</strong>
    </div>
    <slot />
    <p class="summary-note">
      <AppIcon name="truck" :size="16" /> Frete grátis nesta versão da loja.
    </p>
  </aside>
</template>
