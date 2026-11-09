<script setup>
import { onMounted, ref } from "vue";
import { getMyOrders } from "../api/http.js";
import { currency, date, resources } from "../resources.js";
import Icon from "./Icon.vue";

const orders = ref([]);
const loading = ref(true);
const error = ref("");
const statusNames = Object.fromEntries(resources.orders.fields.find(field => field.key === "status").options);
async function load() {
  loading.value = true;
  error.value = "";
  try {
    orders.value = await getMyOrders();
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
</script>

<template>
  <section class="my-orders" :aria-busy="loading">
    <header><span class="eyebrow">3C COMMERCE / SUA CONTA</span><h1>Meus pedidos</h1><p class="muted">Suas compras, das mais recentes às mais antigas.</p></header>
    <div v-if="loading" class="panel empty-state" role="status"><span class="spinner" /> Carregando pedidos…</div>
    <div v-else-if="error" class="panel empty-state" role="alert"><Icon name="alert" :size="32" /><p>{{ error }}</p><button class="button secondary" @click="load">Tentar novamente</button></div>
    <div v-else-if="!orders.length" class="panel empty-state"><Icon name="orders" :size="32" /><h2>Você ainda não tem pedidos</h2><a href="#products" class="button primary">Explorar produtos</a></div>
    <ul v-else class="order-list">
      <li v-for="order in orders" :key="order.id" class="panel order-row">
        <div><a :href="`#order/${order.id}`" class="text-button">Pedido #{{ order.id }}</a><p class="muted">{{ date(order.created_at) }}</p></div>
        <span class="badge" :class="order.status === 'cancelado' ? 'danger' : order.status === 'pendente' ? 'warning' : 'success'">{{ statusNames[order.status] || order.status }}</span>
        <strong>{{ currency(order.total) }}</strong>
        <a :href="`#order/${order.id}`" class="button secondary" :aria-label="`Ver detalhes do pedido #${order.id}`">Ver detalhes</a>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.my-orders { display: grid; gap: 26px; }
.my-orders header p { margin-top: 10px; }
.order-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 16px; }
.order-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; padding: 24px; flex-wrap: wrap; }
.order-row p { margin-top: 8px; }
@media (max-width: 500px) { .order-row { padding: 18px; } .order-row > div { width: 100%; } }
</style>
