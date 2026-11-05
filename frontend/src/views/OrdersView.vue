<script setup>
import { computed, onMounted, ref } from "vue";
import { useRoute } from "vue-router";
import { apiData } from "@/api/http";
import UiState from "@/components/UiState.vue";
import OrderCard from "@/components/OrderCard.vue";
import AppIcon from "@/components/AppIcon.vue";
const route = useRoute();
const orders = ref([]);
const loading = ref(true);
const error = ref("");
const created = computed(() =>
  orders.value.find((order) => String(order.id) === route.query.novo),
);
async function load() {
  loading.value = true;
  error.value = "";
  try {
    orders.value = await apiData("/api/orders");
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
</script>
<template>
  <div class="container page-section narrow-page">
    <div class="page-heading">
      <span class="eyebrow">ACOMPANHE SUAS ESCOLHAS</span>
      <h1>Meus pedidos<span class="heading-dot">.</span></h1>
      <p class="muted">Seu histórico de compras, em um só lugar.</p>
    </div>
    <p v-if="created" class="alert alert-success" role="status">
      <AppIcon name="check" :size="20" /> Pedido #{{ created.id }} realizado!
      Você pode acompanhar o status aqui.
    </p>
    <UiState
      :loading="loading"
      :error="error"
      :empty="!orders.length"
      title="Sua primeira compra começa aqui"
      description="Assim que você fizer um pedido, ele aparecerá nesta página."
      @retry="load"
      ><template #action
        ><RouterLink to="/" class="btn btn-primary"
          >Explorar produtos</RouterLink
        ></template
      >
      <div class="orders-list">
        <OrderCard
          v-for="order in orders"
          :key="order.id"
          :order="order"
        /></div
    ></UiState>
  </div>
</template>
