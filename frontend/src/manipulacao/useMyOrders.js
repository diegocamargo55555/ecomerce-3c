import { onMounted, ref } from "vue";
import { getMyOrders } from "../requisicoes/pedidos.js";
import { statusNames } from "./formatacao.js";

export function useMyOrders() {
  const orders = ref([]);
  const loading = ref(true);
  const error = ref("");
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

  return {
    orders,
    loading,
    error,
    statusNames,
    load,
  };
}
