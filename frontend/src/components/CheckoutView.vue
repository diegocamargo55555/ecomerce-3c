<script setup>
import { currency } from "../manipulacao/formatacao.js";
import Icon from "./Icon.vue";
import { useCheckout } from "../manipulacao/useCheckout.js";

const emit = defineEmits(["created"]);
const {
  quote,
  addresses,
  addressId,
  loading,
  loadError,
  error,
  errors,
  addingAddress,
  savingAddress,
  placing,
  fields,
  form,
  load,
  saveAddress,
  confirm,
} = useCheckout(emit);
</script>

<template>
  <section class="checkout-page" :aria-busy="loading || placing">
    <a href="#cart" class="text-button checkout-back"><Icon name="left" :size="16" /> Voltar ao carrinho</a>
    <span class="eyebrow">40K / FINALIZAR COMPRA</span>
    <h1>Confira sua compra<span>.</span></h1>
    <div v-if="loading" class="panel empty-state" role="status"><span class="spinner" /> Preparando seu pedido…</div>
    <div v-else-if="loadError" class="panel empty-state" role="alert">
      <Icon name="alert" :size="32" /><h2>Não foi possível continuar</h2><p>{{ loadError }}</p>
      <button type="button" class="button secondary" @click="load">Tentar novamente</button>
      <a href="#cart" class="text-button">Revisar carrinho</a>
    </div>
    <div v-else-if="quote" class="checkout-layout">
      <section class="panel checkout-address">
        <h2>Endereço de entrega</h2>
        <p v-if="error" class="alert" role="alert">{{ error }}</p>
        <fieldset v-if="addresses.length" :disabled="placing || savingAddress">
          <legend class="sr-only">Selecione um endereço</legend>
          <label v-for="address in addresses" :key="address.id" class="address-option">
            <input v-model="addressId" type="radio" name="shipping-address" :value="address.id" />
            <span><strong>{{ address.recipient_name }}</strong><br />{{ address.street }}, {{ address.number }}
              <template v-if="address.complement"> · {{ address.complement }}</template><br />
              {{ address.neighborhood }} · {{ address.city }}/{{ address.state }} · CEP {{ address.zip_code }}</span>
          </label>
        </fieldset>
        <button v-if="!addingAddress" type="button" class="text-button" :disabled="placing" @click="addingAddress = true">
          <Icon name="plus" :size="16" /> Cadastrar endereço
        </button>
        <form v-else class="address-form" @submit.prevent="saveAddress">
          <p class="muted">Preencha o endereço para receber seu pedido.</p>
          <div class="form-grid">
            <div v-for="field in fields" :key="field.key" class="field">
              <label :for="`shipping-${field.key}`">{{ field.label }}<span v-if="!field.required" class="optional"> (opcional)</span></label>
              <input :id="`shipping-${field.key}`" v-model="form[field.key]" type="text" :required="field.required"
                :maxlength="field.max" :disabled="savingAddress || placing" :aria-invalid="!!errors[field.key]"
                :aria-describedby="errors[field.key] ? `shipping-error-${field.key}` : undefined" />
              <span v-if="errors[field.key]" :id="`shipping-error-${field.key}`" class="field-error">{{ errors[field.key][0] }}</span>
            </div>
          </div>
          <div class="checkout-actions">
            <button type="submit" class="button secondary" :disabled="savingAddress || placing">{{ savingAddress ? "Salvando…" : "Salvar endereço" }}</button>
            <button v-if="addresses.length" type="button" class="text-button" :disabled="savingAddress || placing" @click="addingAddress = false">Cancelar</button>
          </div>
        </form>
      </section>
      <aside class="panel checkout-summary">
        <h2>Resumo do pedido</h2>
        <ul class="checkout-items">
          <li v-for="item in quote.items" :key="item.product_id">
            <div><strong>{{ item.product_name }}</strong><small>{{ item.quantity }} × {{ currency(item.unit_price) }}</small></div>
            <span>{{ currency(item.subtotal) }}</span>
          </li>
        </ul>
        <dl class="checkout-totals">
          <div><dt>Produtos</dt><dd>{{ currency(quote.subtotal) }}</dd></div>
          <div><dt>Frete fixo</dt><dd>{{ currency(quote.shipping_cost) }}</dd></div>
          <div class="checkout-total"><dt>Total</dt><dd>{{ currency(quote.total) }}</dd></div>
        </dl>
        <small class="muted">Preços e estoque serão conferidos novamente ao confirmar. O pagamento é simulado, sem cobrança real.</small>
        <button type="button" class="button primary" :disabled="placing || savingAddress || addingAddress || !addressId" @click="confirm">
          <span v-if="placing" class="spinner" />{{ placing ? "Criando pedido…" : "Confirmar pedido" }}
        </button>
      </aside>
    </div>
  </section>
</template>

<style scoped>
.checkout-back { margin-bottom: 24px; }
.checkout-page h1 { margin-bottom: 28px; }
.checkout-page h1 span { color: var(--accent-dark); }
.checkout-layout { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr); gap: 24px; align-items: start; }
.checkout-address, .checkout-summary { padding: 26px; display: grid; gap: 20px; }
.checkout-address fieldset { border: 0; padding: 0; margin: 0; display: grid; gap: 12px; min-width: 0; }
.address-option { display: flex; align-items: flex-start; gap: 12px; padding: 16px; border: 1px solid var(--border); border-radius: 2px; cursor: pointer; font-size: 13px; overflow-wrap: anywhere; }
.address-option:has(input:checked) { border-color: var(--primary); background: #d4af3714; }
.address-option input { margin-top: 4px; }
.address-form { display: grid; gap: 18px; }
.checkout-actions { display: flex; gap: 16px; align-items: center; }
.checkout-items { padding: 0; margin: 0; list-style: none; }
.checkout-items li { display: flex; gap: 20px; justify-content: space-between; padding: 14px 0; border-bottom: 1px solid var(--border); }
.checkout-items strong { overflow-wrap: anywhere; }
.checkout-items small { display: block; color: var(--muted); margin-top: 5px; }
.checkout-items li > span { white-space: nowrap; }
.checkout-totals { display: grid; gap: 12px; margin: 0; }
.checkout-totals > div { display: flex; gap: 16px; justify-content: space-between; }
.checkout-totals dd { margin: 0; }
.checkout-total { font-weight: 700; font-size: 22px; padding-top: 14px; border-top: 1px solid var(--border); }
@media (max-width: 800px) { .checkout-layout { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 500px) { .checkout-address, .checkout-summary { padding: 18px; } }
</style>
