<script setup>
import { computed, onMounted, ref } from "vue";
import { apiData } from "@/api/http";
import { notify } from "@/stores/shop";
import { statuses } from "@/utils/format";
import UiState from "@/components/UiState.vue";
import OrderCard from "@/components/OrderCard.vue";
const orders = ref([]);
const loading = ref(true);
const error = ref("");
const filter = ref("");
const changing = ref(null);
const filtered = computed(() =>
  orders.value.filter(
    (order) => !filter.value || order.status === filter.value,
  ),
);
async function load() {
  loading.value = true;
  error.value = "";
  try {
    orders.value = await apiData("/api/admin/orders");
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
async function change(order, status) {
  const prompt =
    status === "cancelado"
      ? `Cancelar o pedido #${order.id}? Os produtos voltarão ao estoque.`
      : `Marcar o pedido #${order.id} como pago?`;
  if (!window.confirm(prompt)) return;
  changing.value = order.id;
  try {
    const updated = await apiData(`/api/admin/orders/${order.id}`, {
      method: "PATCH",
      body: { status },
    });
    orders.value = orders.value.map((item) =>
      item.id === updated.id ? updated : item,
    );
    notify("Status do pedido atualizado.");
  } catch (failure) {
    notify(failure.message, "error");
  } finally {
    changing.value = null;
  }
}
</script>
<template>
  <div>
    <div class="admin-page-heading">
      <div>
        <span class="eyebrow">ACOMPANHAMENTO</span>
        <h1>Pedidos</h1>
        <p class="muted">
          Consulte as compras e atualize os pedidos pendentes.
        </p>
      </div>
      <label class="field"
        ><span class="sr-only">Filtrar por status</span
        ><select v-model="filter">
          <option value="">Todos os status</option>
          <option
            v-for="(label, value) in statuses"
            :key="value"
            :value="value"
          >
            {{ label }}
          </option>
        </select></label
      >
    </div>
    <UiState
      :loading="loading"
      :error="error"
      :empty="!filtered.length"
      title="Nenhum pedido por aqui"
      description="Os pedidos da loja aparecerão nesta lista."
      @retry="load"
      ><div class="orders-list">
        <OrderCard
          v-for="order in filtered"
          :key="order.id"
          :order="order"
          admin
          :busy="changing !== null"
          @status="change(order, $event)"
        /></div
    ></UiState>
  </div>
</template>
