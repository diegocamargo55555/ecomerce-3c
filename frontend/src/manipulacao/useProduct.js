import { computed, onBeforeUnmount, ref, watch } from "vue";
import { addToCart } from "../requisicoes/carrinho.js";
import { getProduct } from "../requisicoes/produtos.js";
import { isValidQuantity } from "../validacoes/produtos.js";

export function useProduct(props, emit) {
  const product = ref(null);
  const loading = ref(true);
  const error = ref("");
  const notFound = ref(false);
  const failedImage = ref(false);
  const quantity = ref(1);
  const saving = ref(false);
  const cartError = ref("");
  const added = ref("");
  let active = true;
  onBeforeUnmount(() => { active = false; });
  const validQuantity = computed(() => isValidQuantity(quantity.value, product.value));

  async function load() {
    loading.value = true;
    error.value = "";
    notFound.value = false;
    failedImage.value = false;
    cartError.value = "";
    added.value = "";
    quantity.value = 1;
    try {
      const result = await getProduct(props.productId);
      if (!active) return;
      product.value = result;
      document.title = `${product.value.name} · 3C Commerce`;
    } catch (failure) {
      notFound.value = failure.status === 404;
      error.value = failure.message;
    } finally {
      loading.value = false;
    }
  }

  async function add() {
    if (!validQuantity.value || saving.value) return;
    saving.value = true;
    cartError.value = "";
    added.value = "";
    try {
      const item = await addToCart(product.value.id, quantity.value);
      emit("cart-updated");
      added.value = `Adicionado ao carrinho. Você tem ${item.quantity} unidade(s) deste produto.`;
    } catch (failure) {
      cartError.value = failure.status === 404
        ? "Este produto não está mais disponível. Atualize os detalhes."
        : failure.errors?.quantity?.[0] || failure.message;
    } finally {
      saving.value = false;
    }
  }

  watch(() => props.productId, load, { immediate: true });

  return {
    product,
    loading,
    error,
    notFound,
    failedImage,
    quantity,
    saving,
    cartError,
    added,
    validQuantity,
    load,
    add,
  };
}
