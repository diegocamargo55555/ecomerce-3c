import { computed, nextTick, onMounted, ref } from "vue";
import { getOrder, payOrder, cancelOrder, updateOrderStatus } from "../requisicoes/pedidos.js";
import { statusNames } from "./formatacao.js";

export function useOrder(props) {
  const order = ref(null);
  const loading = ref(true);
  const error = ref("");
  const actionError = ref("");
  const paying = ref(false);
  const cancelling = ref(false);
  const updatingStatus = ref(false);
  const confirmingCancel = ref(false);
  const notice = ref("");
  const noticeElement = ref(null);
  const busy = computed(() => paying.value || cancelling.value || updatingStatus.value);
  const nextStatus = computed(() => props.user?.role === "admin" ? ({ pago: "enviado", enviado: "entregue" }[order.value?.status] ?? null) : null);
  const canCancel = computed(() => order.value?.status === "pendente" && (props.user?.id === order.value.users_id || props.user?.role === "admin"));
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
  async function advanceStatus() {
    if (busy.value || confirmingCancel.value || !nextStatus.value) return;
    updatingStatus.value = true;
    actionError.value = "";
    notice.value = "";
    try {
      order.value = await updateOrderStatus(props.orderId, nextStatus.value);
      notice.value = `Status atualizado: ${statusNames[order.value.status]}.`;
      await nextTick();
      noticeElement.value?.focus({ preventScroll: true });
    } catch (failure) {
      actionError.value = failure.message;
      if (failure.status === 409 || failure.status === 404) await load();
    } finally {
      updatingStatus.value = false;
    }
  }
  onMounted(load);

  return {
    order,
    loading,
    error,
    actionError,
    paying,
    cancelling,
    updatingStatus,
    confirmingCancel,
    notice,
    noticeElement,
    busy,
    nextStatus,
    canCancel,
    statusNames,
    payment,
    load,
    pay,
    cancel,
    advanceStatus,
  };
}
