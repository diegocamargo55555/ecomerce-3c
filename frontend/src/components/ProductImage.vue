<script setup>
import { computed, ref, watch } from "vue";
import AppIcon from "./AppIcon.vue";
const props = defineProps({ product: { type: Object, default: () => ({}) } });
const failed = ref(false);
watch(
  () => props.product?.image_url,
  () => {
    failed.value = false;
  },
);
const icon = computed(() => {
  const name = props.product?.name?.toLowerCase() || "";
  if (/teclado/.test(name)) return "keyboard";
  if (/fone/.test(name)) return "headphones";
  if (/phone|celular/.test(name)) return "phone";
  if (/camiseta|roupa|jaqueta/.test(name)) return "shirt";
  if (/computador|notebook/.test(name)) return "laptop";
  return "box";
});
</script>
<template>
  <div class="product-image" :class="`illustration-${icon}`">
    <img
      v-if="product?.image_url && !failed"
      :src="product.image_url"
      :alt="product.name"
      loading="lazy"
      @error="failed = true"
    />
    <div
      v-else
      class="image-fallback"
      role="img"
      :aria-label="`${product?.name || 'Produto'}: imagem indisponível`"
    >
      <AppIcon :name="icon" :size="76" /><span>Sem foto</span>
    </div>
  </div>
</template>
