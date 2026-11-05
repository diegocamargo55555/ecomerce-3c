<script setup>
import { onMounted, ref } from "vue";
import {
  cart,
  cartTotal,
  cartCount,
  invalidCart,
  loadCart,
  changeItem,
} from "@/stores/shop";
import { money } from "@/utils/format";
import UiState from "@/components/UiState.vue";
import ProductImage from "@/components/ProductImage.vue";
import AppIcon from "@/components/AppIcon.vue";
import CartSummary from "@/components/CartSummary.vue";
const loading = ref(true);
const error = ref("");
const actionError = ref("");
async function load() {
  loading.value = true;
  error.value = "";
  try {
    await loadCart();
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
async function change(item, quantity) {
  actionError.value = "";
  try {
    await changeItem(item, quantity);
  } catch (failure) {
    actionError.value = failure.message;
  }
}
</script>
<template>
  <div class="container page-section">
    <div class="page-heading">
      <span class="eyebrow">SUAS ESCOLHAS</span>
      <h1>Meu carrinho<span class="heading-dot">.</span></h1>
      <p class="muted">Mais um passo para levar seus favoritos.</p>
    </div>
    <UiState
      :loading="loading"
      :error="error"
      :empty="!cart.items.length"
      title="Seu carrinho está esperando por você"
      description="Explore o catálogo e adicione seus primeiros produtos."
      icon="cart"
      @retry="load"
      ><template #action
        ><RouterLink to="/" class="btn btn-primary"
          >Explorar produtos <AppIcon name="arrow" :size="18" /></RouterLink
      ></template>
      <p v-if="actionError" class="alert alert-error" role="alert">
        {{ actionError }}
      </p>
      <p v-if="invalidCart" class="alert alert-error" role="alert">
        Há produtos indisponíveis ou com estoque insuficiente. Ajuste as
        quantidades ou remova-os para continuar.
      </p>
      <div class="shopping-layout">
        <div>
          <div class="cart-items">
            <article
              v-for="item in cart.items"
              :key="item.id"
              class="cart-item"
            >
              <RouterLink
                v-if="item.product"
                :to="`/produtos/${item.product_id}`"
                class="cart-item-image"
                ><ProductImage :product="item.product"
              /></RouterLink>
              <div v-else class="cart-item-image"><ProductImage /></div>
              <div class="cart-item-copy">
                <RouterLink
                  v-if="item.product"
                  :to="`/produtos/${item.product_id}`"
                  >{{ item.product.name }}</RouterLink
                ><strong v-else>Produto indisponível</strong>
                <p>
                  {{ money(item.product?.price) }}
                  <span class="muted">/ unidade</span>
                </p>
                <span
                  v-if="
                    !item.product?.is_active ||
                    item.quantity > item.product?.stock
                  "
                  class="field-error"
                  >Revise este produto</span
                ><button
                  class="text-button muted"
                  :disabled="cart.busy"
                  @click="change(item, 0)"
                >
                  <AppIcon name="trash" :size="14" /> Remover
                </button>
              </div>
              <div class="cart-item-end">
                <strong>{{
                  money(Number(item.product?.price || 0) * item.quantity)
                }}</strong>
                <div class="quantity-stepper">
                  <button
                    :disabled="
                      cart.busy ||
                      item.quantity <= 1 ||
                      !item.product?.is_active
                    "
                    :aria-label="`Diminuir quantidade de ${item.product?.name || 'produto'}`"
                    @click="change(item, item.quantity - 1)"
                  >
                    <AppIcon name="minus" :size="14" /></button
                  ><span aria-live="polite">{{ item.quantity }}</span
                  ><button
                    :disabled="
                      cart.busy ||
                      !item.product?.is_active ||
                      item.quantity >= item.product.stock
                    "
                    :aria-label="`Aumentar quantidade de ${item.product?.name || 'produto'}`"
                    @click="change(item, item.quantity + 1)"
                  >
                    <AppIcon name="plus" :size="14" />
                  </button>
                </div>
              </div>
            </article>
          </div>
          <RouterLink to="/" class="back-link"
            ><AppIcon name="back" :size="17" /> Continuar comprando</RouterLink
          >
        </div>
        <CartSummary :total="cartTotal" :count="cartCount"
          ><RouterLink
            v-if="!invalidCart && !cart.busy"
            to="/checkout"
            class="btn btn-primary full-width"
            >Finalizar pedido <AppIcon name="arrow" :size="18" /></RouterLink
          ><button v-else class="btn btn-primary full-width" disabled>
            Finalizar pedido
          </button></CartSummary
        >
      </div></UiState
    >
  </div>
</template>
