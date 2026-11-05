<script setup>
import { ref, watch } from "vue";
import { useRoute } from "vue-router";
import { apiData } from "@/api/http";
import { cart } from "@/stores/shop";
import { useShopping } from "@/composables/useShopping";
import { money } from "@/utils/format";
import ProductImage from "@/components/ProductImage.vue";
import UiState from "@/components/UiState.vue";
import AppIcon from "@/components/AppIcon.vue";
const route = useRoute();
const product = ref(null);
const quantity = ref(1);
const loading = ref(true);
const error = ref("");
const add = useShopping();
async function load() {
  loading.value = true;
  error.value = "";
  quantity.value = 1;
  try {
    product.value = await apiData(`/api/products/${route.params.id}`);
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
watch(() => route.params.id, load, { immediate: true });
</script>
<template>
  <div class="container page-section">
    <RouterLink to="/" class="back-link"
      ><AppIcon name="back" :size="17" /> Voltar ao catálogo</RouterLink
    ><UiState :loading="loading" :error="error" @retry="load"
      ><section v-if="product" class="product-detail">
        <ProductImage :product="product" />
        <div class="product-detail-copy">
          <span class="eyebrow">SEU PRÓXIMO FAVORITO</span>
          <h1>{{ product.name }}</h1>
          <span
            :class="['badge', product.stock ? 'badge-pago' : 'badge-cancelado']"
            >{{ product.stock ? "Em estoque" : "Indisponível" }}</span
          >
          <p class="detail-description">{{ product.description }}</p>
          <strong class="detail-price">{{ money(product.price) }}</strong>
          <p class="detail-shipping">
            <AppIcon name="truck" :size="19" /> Frete grátis
          </p>
          <form @submit.prevent="add(product, quantity)">
            <label class="quantity-label" for="quantity">Quantidade</label>
            <div class="purchase-controls">
              <div class="quantity-stepper">
                <button
                  type="button"
                  :disabled="quantity <= 1 || !product.stock"
                  aria-label="Diminuir quantidade"
                  @click="quantity--"
                >
                  <AppIcon name="minus" :size="16" /></button
                ><input
                  id="quantity"
                  v-model.number="quantity"
                  type="number"
                  min="1"
                  :max="product.stock"
                  required
                  :disabled="!product.stock"
                /><button
                  type="button"
                  :disabled="quantity >= product.stock || !product.stock"
                  aria-label="Aumentar quantidade"
                  @click="quantity++"
                >
                  <AppIcon name="plus" :size="16" />
                </button>
              </div>
              <button
                class="btn btn-primary"
                :disabled="!product.stock || cart.busy"
              >
                <AppIcon name="cart" :size="19" />
                {{ cart.busy ? "Adicionando..." : "Adicionar ao carrinho" }}
              </button>
            </div>
          </form>
          <p class="muted small">
            {{
              product.stock
                ? `${product.stock} unidades disponíveis`
                : "Este produto está sem estoque no momento."
            }}
          </p>
        </div>
      </section></UiState
    >
  </div>
</template>
