<script setup>
import { cart } from "@/stores/shop";
import { useShopping } from "@/composables/useShopping";
import { money } from "@/utils/format";
import AppIcon from "./AppIcon.vue";
import ProductImage from "./ProductImage.vue";
defineProps({ product: Object, category: String });
const add = useShopping();
</script>
<template>
  <article class="product-card">
    <RouterLink
      :to="`/produtos/${product.id}`"
      class="product-visual"
      :aria-label="`Ver ${product.name}`"
      ><ProductImage :product="product" /><span
        v-if="!product.stock"
        class="stock-label"
        >Indisponível</span
      ></RouterLink
    >
    <div class="product-info">
      <span class="eyebrow">{{ category || "Loja 3C" }}</span
      ><RouterLink :to="`/produtos/${product.id}`" class="product-name">{{
        product.name
      }}</RouterLink>
      <p class="product-description">{{ product.description }}</p>
      <div class="product-bottom">
        <strong>{{ money(product.price) }}</strong
        ><button
          class="btn btn-primary icon-btn"
          :disabled="!product.stock || cart.busy"
          :aria-label="`Adicionar ${product.name} ao carrinho`"
          @click="add(product)"
        >
          <AppIcon name="plus" />
        </button>
      </div>
    </div>
  </article>
</template>
