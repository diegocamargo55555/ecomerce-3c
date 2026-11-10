import { computed, onMounted, ref } from "vue";
import { getOverview } from "../requisicoes/visaoGeral.js";
import { currency } from "./formatacao.js";

export function useOverview(emit) {
  // null: os números não puderam ser carregados.
  const data = ref(null);
  const loading = ref(true);
  const error = ref("");
  async function load() {
    loading.value = true;
    error.value = "";
    try {
      data.value = await getOverview();
    } catch {
      data.value = null;
      error.value =
        "Os dados não puderam ser carregados. Tente atualizar o painel.";
    }
    loading.value = false;
  }
  onMounted(load);
  const lowStock = computed(() => data.value?.low_stock || []);
  const recentOrders = computed(() => data.value?.recent_orders || []);
  const stats = computed(() => [
    {
      title: "Total de pedidos",
      value: data.value?.orders_count ?? "—",
      icon: "orders",
      caption: "Pedidos registrados",
      target: "orders",
    },
    {
      title: "Produtos ativos",
      value: data.value?.active_products_count ?? "—",
      icon: "box",
      caption: "Disponíveis no catálogo",
      target: "products",
    },
    {
      title: "Pagamentos recebidos",
      value: data.value ? currency(data.value.paid_total) : "—",
      icon: "wallet",
      caption: "Total com status pago",
      target: "payments",
    },
    {
      title: "Atenção ao estoque",
      value: data.value?.low_stock_count ?? "—",
      icon: "alert",
      caption: "Produtos com até 5 unidades",
      target: "products",
    },
  ]);
  const today = new Intl.DateTimeFormat("pt-BR", {
    day: "numeric",
    month: "long",
    year: "numeric",
  }).format(new Date());

  return {
    data,
    loading,
    error,
    load,
    lowStock,
    recentOrders,
    stats,
    today,
  };
}
