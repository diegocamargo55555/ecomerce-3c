<script setup>
import { computed, onMounted, ref } from "vue";
import { getOrder, payOrder } from "../api/http.js";
import { currency, date, resources } from "../resources.js";
import Icon from "./Icon.vue";

const props = defineProps({ orderId: String, user: Object });
const order = ref(null);
const loading = ref(true);
const error = ref("");
const paymentError = ref("");
const paying = ref(false);
const statusNames = Object.fromEntries(resources.orders.fields.find(field => field.key === "status").options);
const payment = computed(() => order.value?.payments.find(item => item.status === "pago"));

async function load() {
  loading.value = true;
  error.value = "";
  try {
    order.value = await getOrder(props.orderId);
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
async function pay() {
  if (paying.value) return;
  paying.value = true;
  paymentError.value = "";
  try {
    order.value = await payOrder(props.orderId);
  } catch (failure) {
    paymentError.value = failure.message;
    if (failure.status === 409) await load();
  } finally {
    paying.value = false;
  }
}
onMounted(load);
</script>

<template>
  <section class="order-page" :aria-busy="loading || paying">
    <a href="#products" class="text-button order-back"><Icon name="left" :size="16" /> Voltar à loja</a>
    <div v-if="loading" class="panel empty-state" role="status"><span class="spinner" /> Carregando pedido…</div>
    <div v-else-if="error" class="panel empty-state" role="alert">
      <Icon name="alert" :size="32" /><h1>Pedido indisponível</h1><p>{{ error }}</p>
      <button type="button" class="button secondary" @click="load">Tentar novamente</button>
    </div>
    <template v-else-if="order">
      <header class="order-heading">
        <div><span class="eyebrow">3C COMMERCE / SEU PEDIDO</span><h1>Pedido #{{ order.id }}</h1><p class="muted">Criado em {{ date(order.created_at) }}</p></div>
        <span class="badge" :class="payment ? 'success' : 'warning'">{{ statusNames[order.status] || order.status }}</span>
      </header>
      <div class="order-layout">
        <section class="panel order-details">
          <h2>Itens do pedido</h2>
          <ul class="order-items">
            <li v-for="item in order.items" :key="item.id">
              <div><strong>{{ item.product_name }}</strong><small>{{ item.quantity }} × {{ currency(item.unit_price) }}</small></div>
              <span>{{ currency(item.subtotal) }}</span>
            </li>
          </ul>
          <h2>Endereço de entrega</h2>
          <address v-if="order.address">
            <strong>{{ order.address.recipient_name }}</strong><br />
            {{ order.address.street }}, {{ order.address.number }}<template v-if="order.address.complement"> · {{ order.address.complement }}</template><br />
            {{ order.address.neighborhood }} · {{ order.address.city }}/{{ order.address.state }}<br />CEP {{ order.address.zip_code }}
          </address>
        </section>
        <aside class="panel order-payment">
          <h2>{{ payment ? "Pagamento confirmado" : "Pagamento do pedido" }}</h2>
          <p class="notice">Pagamento simulado para fins acadêmicos. Nenhuma cobrança real será realizada.</p>
          <dl class="order-totals">
            <div><dt>Produtos</dt><dd>{{ currency(order.subtotal) }}</dd></div>
            <div><dt>Frete</dt><dd>{{ currency(order.shipping_cost) }}</dd></div>
            <div class="order-total"><dt>Total</dt><dd>{{ currency(order.total) }}</dd></div>
          </dl>
          <p v-if="payment" class="order-paid" role="status"><Icon name="check" :size="20" /> Confirmado em {{ new Date(payment.paid_at).toLocaleString('pt-BR') }}</p>
          <p v-if="paymentError" class="alert" role="alert">{{ paymentError }}</p>
          <button v-if="order.status === 'pendente' && user?.id === order.users_id" type="button" class="button primary" :disabled="paying" @click="pay">
            <span v-if="paying" class="spinner" />{{ paying ? "Confirmando…" : "Confirmar pagamento simulado" }}
          </button>
          <small class="muted">Guarde o endereço desta página para consultar seu pedido novamente.</small>
        </aside>
      </div>
    </template>
  </section>
</template>

<style scoped>
.order-back { margin-bottom: 26px; }
.order-heading { display: flex; gap: 20px; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.order-heading p { margin-top: 8px; }
.order-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 24px; align-items: start; }
.order-details, .order-payment { padding: 26px; display: grid; gap: 20px; }
.order-items { padding: 0; margin: 0; list-style: none; }
.order-items li { display: flex; justify-content: space-between; gap: 20px; padding: 14px 0; border-bottom: 1px solid var(--border); }
.order-items strong { overflow-wrap: anywhere; }
.order-items small { display: block; color: var(--muted); margin-top: 5px; }
.order-items li > span { white-space: nowrap; }
.order-details address { font-style: normal; color: var(--muted); line-height: 1.8; overflow-wrap: anywhere; }
.order-totals { display: grid; gap: 12px; margin: 0; }
.order-totals > div { display: flex; justify-content: space-between; gap: 16px; }
.order-totals dd { margin: 0; }
.order-total { font-size: 24px; font-weight: 700; padding-top: 16px; border-top: 1px solid var(--border); }
.order-paid { display: flex; align-items: center; gap: 8px; color: #157447; }
@media (max-width: 800px) { .order-layout { grid-template-columns: minmax(0, 1fr); } }
@media (max-width: 500px) { .order-details, .order-payment { padding: 18px; } .order-heading { align-items: flex-start; flex-direction: column; } }
</style>
