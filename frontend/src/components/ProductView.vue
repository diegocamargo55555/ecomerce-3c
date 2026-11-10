<script setup>
import { currency } from "../manipulacao/formatacao.js";
import Icon from "./Icon.vue";
import { useProduct } from "../manipulacao/useProduct.js";

const props = defineProps({ productId: String });
const emit = defineEmits(["cart-updated"]);
const {
  product,
  loading,
  error,
  notFound,
  failedImage,
  quantity,
  saving,
  cartError,
  added,
  validQuantity,
  load,
  add,
} = useProduct(props, emit);
</script>

<template>
  <section class="product-page" :aria-busy="loading">
    <a href="#products" class="text-button product-back">
      <Icon name="left" :size="16" /> Voltar ao catálogo
    </a>
    <div v-if="loading" class="panel empty-state" role="status">
      <span class="spinner" /><p>Carregando produto…</p>
    </div>
    <div v-else-if="error" class="panel empty-state">
      <Icon :name="notFound ? 'box' : 'alert'" :size="32" />
      <h1>{{ notFound ? "Produto não encontrado" : "Não foi possível carregar o produto" }}</h1>
      <p role="alert">{{ notFound ? "Este produto não existe ou não está disponível no catálogo." : error }}</p>
      <a v-if="notFound" class="button primary" href="#products">Explorar produtos</a>
      <button v-else class="button primary" @click="load">Tentar novamente</button>
    </div>
    <article v-else-if="product" class="product-layout">
      <div class="product-photo">
        <img v-if="product.image_url && !failedImage" :src="product.image_url"
          :alt="product.name" @error="failedImage = true" />
        <span v-else class="product-placeholder"><Icon name="box" :size="64" />Imagem indisponível</span>
      </div>
      <div class="product-info">
        <span class="eyebrow">{{ product.category?.name || "Sem categoria" }}</span>
        <h1>{{ product.name }}</h1>
        <p class="product-stock" :class="{ unavailable: !product.stock }">
          <Icon :name="product.stock ? 'check' : 'alert'" :size="17" />
          {{ product.stock ? `${product.stock} unidade(s) em estoque` : "Produto sem estoque" }}
        </p>
        <strong class="product-price">{{ currency(product.price) }}</strong>
        <form class="product-purchase" @submit.prevent="add">
          <label for="product-quantity">Quantidade</label>
          <div class="product-actions">
            <input id="product-quantity" v-model.number="quantity" type="number"
              min="1" :max="product.stock" step="1" required
              :disabled="!product.stock || saving" aria-describedby="quantity-help"
              @input="added = ''; cartError = ''" />
            <button class="button primary" type="submit" :disabled="!validQuantity || saving">
              <span v-if="saving" class="spinner" /><Icon v-else name="cart" :size="19" />
              {{ saving ? "Adicionando…" : product.stock ? "Adicionar ao carrinho" : "Indisponível" }}
            </button>
          </div>
          <small id="quantity-help" class="muted">{{ product.stock ? `Máximo de ${product.stock} unidade(s).` : "Aguarde a reposição deste produto." }}</small>
          <p v-if="added" class="notice" role="status">{{ added }} <a href="#cart" class="text-button">Ver carrinho</a></p>
          <div v-if="cartError" class="product-cart-error" role="alert">
            <p>{{ cartError }}</p><button type="button" class="text-button" @click="load">Atualizar detalhes</button>
          </div>
        </form>
        <div class="product-description">
          <h2>Sobre o produto</h2>
          <p>{{ product.description || "Este produto ainda não possui descrição." }}</p>
        </div>
      </div>
    </article>
  </section>
</template>

<style scoped>
.product-back { margin-bottom: 28px; }
.product-layout { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 48px; align-items: start; }
.product-photo { display: grid; place-items: center; aspect-ratio: 1; padding: 36px; border: 1px solid var(--border); border-radius: 14px; background: var(--surface); }
.product-photo img { width: 100%; height: 100%; min-height: 0; object-fit: contain; }
.product-placeholder { display: flex; flex-direction: column; align-items: center; gap: 14px; color: var(--muted); }
.product-info { padding: 18px 0; min-width: 0; }
.product-info h1 { overflow-wrap: anywhere; }
.product-stock { display: flex; align-items: center; gap: 7px; color: #157447; margin-top: 18px; font-size: 13px; }
.product-stock.unavailable { color: var(--danger); }
.product-price { display: block; font-size: 38px; font-weight: 750; letter-spacing: -1.3px; margin: 24px 0; }
.product-purchase { display: grid; gap: 10px; }
.product-purchase label { font-weight: 600; font-size: 13px; }
.product-actions { display: flex; gap: 12px; }
.product-actions input { width: 80px; min-height: 48px; padding: 10px; border: 1px solid var(--border); border-radius: 7px; background: var(--surface); }
.product-actions .button { flex: 1; font-size: 14px; }
.product-cart-error { display: grid; gap: 8px; color: var(--danger); }
.product-description { border-top: 1px solid var(--border); margin-top: 30px; padding-top: 24px; }
.product-description p { color: var(--muted); white-space: pre-line; overflow-wrap: anywhere; margin-top: 12px; line-height: 1.8; }
@media (max-width: 760px) {
  .product-layout { grid-template-columns: minmax(0, 1fr); gap: 16px; }
  .product-photo { max-height: 400px; width: 100%; aspect-ratio: 1.2; }
  .product-info h1 { font-size: 26px; }
  .product-price { font-size: 32px; }
}
</style>
