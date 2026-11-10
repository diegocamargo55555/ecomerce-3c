<script setup>
import { currency } from "../manipulacao/formatacao.js";
import Icon from "./Icon.vue";
import { useCart } from "../manipulacao/useCart.js";

const props = defineProps({ cart: Object, loading: Boolean, error: String, user: Object });
const emit = defineEmits(["refresh", "updated"]);
const {
  quantities,
  busy,
  itemError,
  errorItem,
  notice,
  failedImages,
  change,
} = useCart(props, emit);
</script>

<template>
  <section class="cart-page" :aria-busy="loading || busy">
    <header class="cart-heading">
      <div><span class="eyebrow">3C COMMERCE / SUAS ESCOLHAS</span><h1>Meu carrinho<span>.</span></h1></div>
      <a href="#products" class="text-button"><Icon name="left" :size="16" /> Continuar comprando</a>
    </header>
    <div v-if="error" class="alert" role="alert">
      <p>{{ error }}</p><button type="button" class="text-button" @click="emit('refresh')">Tentar novamente</button>
    </div>
    <p v-if="notice" class="notice cart-notice" role="status">{{ notice }}</p>
    <p v-if="itemError" class="alert cart-notice" role="alert">{{ itemError }}</p>
    <div v-if="loading && !cart" class="panel empty-state" role="status"><span class="spinner" /> Carregando carrinho…</div>
    <div v-else-if="cart && !cart.items.length" class="panel empty-state">
      <Icon name="cart" :size="40" /><h2>Seu carrinho está vazio</h2>
      <p>Explore o catálogo e encontre seus próximos produtos.</p>
      <a href="#products" class="button primary">Explorar produtos</a>
    </div>
    <div v-else-if="cart" class="cart-layout">
      <ul class="cart-items">
        <li v-for="item in cart.items" :key="item.id" class="panel cart-item">
          <a :href="`#products/${item.product_id}`" class="cart-image" :aria-label="`Ver ${item.product.name}`">
            <img v-if="item.product.image_url && !failedImages.has(item.id)" :src="item.product.image_url"
              :alt="item.product.name" @error="failedImages.add(item.id)" />
            <Icon v-else name="box" :size="38" />
          </a>
          <div class="cart-item-body">
            <a :href="`#products/${item.product_id}`"><h2>{{ item.product.name }}</h2></a>
            <p class="muted cart-unit-price">{{ currency(item.product.price) }} por unidade</p>
            <p v-if="!item.available" class="cart-unavailable"><Icon name="alert" :size="16" />{{ item.unavailable_reason }}</p>
            <form class="cart-item-actions" @submit.prevent="change(item)">
              <label :for="`quantity-${item.id}`">Quantidade</label>
              <input :id="`quantity-${item.id}`" v-model.number="quantities[item.id]" type="number" min="0"
                :max="item.product.stock" step="1" required :aria-invalid="errorItem === item.id && !!itemError"
                :disabled="busy || loading || !item.product.is_active || !item.product.stock" />
              <button type="submit" class="button secondary"
                :disabled="busy || loading || !item.product.is_active || !item.product.stock || quantities[item.id] === item.quantity">Atualizar</button>
              <button type="button" class="text-button cart-remove" :disabled="busy || loading"
                :aria-label="`Remover ${item.product.name}`" @click="change(item, true)"><Icon name="trash" :size="16" /> Remover</button>
            </form>
          </div>
          <div class="cart-subtotal"><small class="muted">Subtotal</small><strong>{{ currency(item.subtotal) }}</strong></div>
        </li>
      </ul>
      <aside class="panel cart-summary" aria-label="Resumo do carrinho">
        <h2>Resumo do carrinho</h2>
        <p>{{ cart.item_count }} unidade(s) em {{ cart.items.length }} produto(s)</p>
        <div class="cart-total"><span>Total</span><strong>{{ currency(cart.total) }}</strong></div>
        <small class="muted">Frete não incluído. Os preços acompanham o catálogo.</small>
        <a v-if="!busy && !loading && !error && cart.items.every(item => item.available)" href="#checkout" class="button primary">
          Finalizar compra <Icon name="right" :size="16" />
        </a>
        <button v-else type="button" class="button primary" disabled>Finalizar compra</button>
        <small v-if="cart.items.some(item => !item.available)" class="muted">Ajuste os itens indisponíveis antes de continuar.</small>
        <template v-if="!user">
          <small class="muted">Entre ou crie uma conta para finalizar. Seu carrinho será preservado.</small>
        </template>
      </aside>
    </div>
  </section>
</template>

<style scoped>
.cart-heading { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 30px; }
.cart-heading h1 { font-size: 36px; }
.cart-heading h1 span { color: var(--accent-dark); }
.cart-heading .eyebrow { color: var(--primary); }
.cart-notice { margin-bottom: 20px; }
.cart-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 24px; align-items: start; }
.cart-items { display: grid; gap: 16px; padding: 0; margin: 0; list-style: none; }
.cart-item { display: grid; grid-template-columns: 100px minmax(0, 1fr) auto; gap: 20px; padding: 22px; align-items: start; }
.cart-image { display: grid; place-items: center; height: 100px; padding: 8px; border-radius: 8px; background: var(--soft); color: var(--muted); }
.cart-image img { width: 100%; height: 100%; min-height: 0; object-fit: contain; }
.cart-item-body h2 { font-size: 15px; overflow-wrap: anywhere; }
.cart-unit-price { margin: 6px 0 14px; font-size: 12px; }
.cart-unavailable { display: flex; align-items: center; gap: 6px; color: var(--danger); font-size: 12px; margin-bottom: 14px; }
.cart-item-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.cart-item-actions label { font-size: 12px; }
.cart-item-actions input { width: 68px; padding: 9px; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); }
.cart-remove { color: var(--danger); }
.cart-subtotal { display: grid; gap: 5px; text-align: right; }
.cart-subtotal strong { white-space: nowrap; font-size: 17px; }
.cart-summary { padding: 24px; display: grid; gap: 18px; }
.cart-summary p { color: var(--muted); font-size: 12px; }
.cart-total { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 20px; }
.cart-total strong { font-size: 24px; }
@media (max-width: 1000px) { .cart-layout { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 600px) {
  .cart-heading { align-items: flex-start; flex-direction: column; }
  .cart-heading h1 { font-size: 28px; }
  .cart-item { grid-template-columns: 70px minmax(0, 1fr); gap: 14px; padding: 16px; }
  .cart-image { height: 70px; }
  .cart-subtotal { grid-column: 2; text-align: left; }
}
</style>
