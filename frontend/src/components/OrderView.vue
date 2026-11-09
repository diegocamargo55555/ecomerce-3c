<script setup>
import { computed, onMounted, ref } from "vue";
import { getOrder, payOrder, cancelOrder } from "../api/http.js";
import { currency, date, resources } from "../resources.js";
import Icon from "./Icon.vue";
import Modal from "./Modal.vue";

const props = defineProps({ orderId: String, user: Object });
const order = ref(null);
const loading = ref(true);
const error = ref("");
const actionError = ref("");
const paying = ref(false);
const cancelling = ref(false);
const confirmingCancel = ref(false);
const notice = ref("");
const busy = computed(() => paying.value || cancelling.value);
const canCancel = computed(() => order.value?.status === "pendente" && (props.user?.id === order.value.users_id || props.user?.role === "admin"));
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
  if (busy.value || confirmingCancel.value) return;
  paying.value = true;
  actionError.value = "";
  try {
    order.value = await payOrder(props.orderId);
  } catch (failure) {
    actionError.value = failure.message;
    if (failure.status === 409) await load();
  } finally {
    paying.value = false;
  }
}
async function cancel() {
  if (busy.value) return;
  cancelling.value = true;
  actionError.value = "";
  try {
    order.value = await cancelOrder(props.orderId);
    notice.value = "Pedido cancelado. Os itens foram devolvidos ao estoque.";
    confirmingCancel.value = false;
  } catch (failure) {
    actionError.value = failure.message;
    if (failure.status === 409 || failure.status === 404) {
      confirmingCancel.value = false;
      await load();
    }
  } finally {
    cancelling.value = false;
  }
}
onMounted(load);
</script>

<template>
  <section class="order-page" :aria-busy="loading || busy">
    <a href="#products" class="text-button order-back"><Icon name="left" :size="16" /> Voltar à loja</a>
    <a href="#my-orders" class="text-button order-back">Meus pedidos</a>
    <a v-if="user?.role === 'admin'" href="#orders" class="text-button order-back">Todos os pedidos</a>
    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <div v-if="loading" class="panel empty-state" role="status"><span class="spinner" /> Carregando pedido…</div>
    <div v-else-if="error" class="panel empty-state" role="alert">
      <Icon name="alert" :size="32" /><h1>Pedido indisponível</h1><p>{{ error }}</p>
      <button type="button" class="button secondary" @click="load">Tentar novamente</button>
    </div>
    <template v-else-if="order">
      <header class="order-heading">
        <div><span class="eyebrow">3C COMMERCE / SEU PEDIDO</span><h1>Pedido #{{ order.id }}</h1><p class="muted">Criado em {{ date(order.created_at) }}</p></div>
        <span class="badge" :class="order.status === 'cancelado' ? 'danger' : payment ? 'success' : 'warning'">{{ statusNames[order.status] || order.status }}</span>
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
          <h2>{{ order.status === "cancelado" ? "Pedido cancelado" : payment ? "Pagamento confirmado" : "Pagamento do pedido" }}</h2>
          <p class="notice">Pagamento simulado para fins acadêmicos. Nenhuma cobrança real será realizada.</p>
          <dl class="order-totals">
            <div><dt>Produtos</dt><dd>{{ currency(order.subtotal) }}</dd></div>
            <div><dt>Frete</dt><dd>{{ currency(order.shipping_cost) }}</dd></div>
            <div class="order-total"><dt>Total</dt><dd>{{ currency(order.total) }}</dd></div>
          </dl>
          <p v-if="payment" class="order-paid" role="status"><Icon name="check" :size="20" /> Confirmado em {{ new Date(payment.paid_at).toLocaleString('pt-BR') }}</p>
          <p v-if="actionError" class="alert" role="alert">{{ actionError }}</p>
          <button v-if="order.status === 'pendente' && user?.id === order.users_id" type="button" class="button primary" :disabled="busy || confirmingCancel" @click="pay">
            <span v-if="paying" class="spinner" />{{ paying ? "Confirmando…" : "Confirmar pagamento simulado" }}
          </button>
          <button v-if="canCancel" type="button" class="button danger" :disabled="busy" @click="actionError = ''; confirmingCancel = true">Cancelar pedido</button>
          <p v-if="order.status === 'cancelado'" class="muted">Este pedido foi cancelado e não pode ser pago.</p>
        </aside>
      </div>
    </template>
    <Modal v-if="confirmingCancel" title="Cancelar pedido?" :busy="busy" small @close="confirmingCancel = false">
      <div class="modal-body">
        <p>Cancelar o pedido #{{ order.id }}? Os itens voltarão ao estoque. Esta ação não pode ser desfeita.</p>
        <p v-if="actionError" class="alert" role="alert">{{ actionError }}</p>
      </div>
      <footer class="modal-footer">
        <button type="button" class="button secondary" :disabled="busy" @click="confirmingCancel = false">Manter pedido</button>
        <button type="button" class="button danger" :disabled="busy" @click="cancel"><span v-if="cancelling" class="spinner" />{{ cancelling ? "Cancelando…" : "Confirmar cancelamento" }}</button>
      </footer>
    </Modal>
  </section>
</template>

<style scoped>
.order-back { margin: 0 20px 26px 0; }
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
