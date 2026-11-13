import { reactive, ref, watch } from "vue";
import { removeCartItem, updateCartItem } from "../requisicoes/carrinho.js";

export function useCart(props, emit) {
  const quantities = reactive({});
  const busy = ref(false);
  const itemError = ref("");
  const errorItem = ref(null);
  const notice = ref("");
  const failedImages = ref(new Set());
  watch(() => props.cart, (cart) => {
    Object.keys(quantities).forEach((key) => delete quantities[key]);
    for (const item of cart?.items || []) quantities[item.id] = item.quantity;
  }, { immediate: true });

  async function change(item, remove = false) {
    if (busy.value) return;
    busy.value = true;
    itemError.value = "";
    errorItem.value = item.id;
    notice.value = "";
    try {
      const cart = remove
        ? await removeCartItem(item.id)
        : await updateCartItem(item.id, quantities[item.id]);
      emit("updated", cart);
      notice.value = remove || quantities[item.id] === 0 ? "Produto removido do carrinho." : "Quantidade atualizada.";
    } catch (failure) {
      itemError.value = failure.errors?.quantity?.[0] || failure.message;
      emit("refresh");
    } finally {
      busy.value = false;
    }
  }

  return {
    quantities,
    busy,
    itemError,
    errorItem,
    notice,
    failedImages,
    change,
  };
}
