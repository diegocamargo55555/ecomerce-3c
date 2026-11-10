import { onMounted, reactive, ref } from "vue";
import { createAddress, getAddresses } from "../requisicoes/perfil.js";
import { createOrder, getCheckout } from "../requisicoes/checkout.js";
import { resources } from "../resources.js";

export function useCheckout(emit) {
  const quote = ref(null);
  const addresses = ref([]);
  const addressId = ref("");
  const loading = ref(true);
  const loadError = ref("");
  const error = ref("");
  const errors = ref({});
  const addingAddress = ref(false);
  const savingAddress = ref(false);
  const placing = ref(false);
  const fields = resources.addresses.fields.filter(field => field.key !== "users_id");
  const form = reactive(Object.fromEntries(fields.map(field => [field.key, ""])));

  async function load() {
    loading.value = true;
    loadError.value = "";
    try {
      [quote.value, addresses.value] = await Promise.all([getCheckout(), getAddresses()]);
      if (!addresses.value.some(address => address.id === addressId.value)) addressId.value = addresses.value[0]?.id ?? "";
      addingAddress.value = !addresses.value.length;
    } catch (failure) {
      loadError.value = failure.errors?.cart?.[0] || failure.message;
    } finally {
      loading.value = false;
    }
  }

  async function saveAddress() {
    if (savingAddress.value) return;
    savingAddress.value = true;
    error.value = "";
    errors.value = {};
    try {
      const address = await createAddress(form);
      addresses.value.unshift(address);
      addressId.value = address.id;
      addingAddress.value = false;
      Object.keys(form).forEach(key => { form[key] = ""; });
    } catch (failure) {
      error.value = failure.message;
      errors.value = failure.errors || {};
    } finally {
      savingAddress.value = false;
    }
  }

  async function confirm() {
    if (placing.value || !addressId.value || addingAddress.value) return;
    placing.value = true;
    error.value = "";
    try {
      const order = await createOrder(addressId.value);
      emit("created", order);
    } catch (failure) {
      error.value = failure.errors?.cart?.[0] || failure.errors?.address_id?.[0] || failure.message;
    } finally {
      placing.value = false;
    }
  }
  onMounted(load);

  return {
    quote,
    addresses,
    addressId,
    loading,
    loadError,
    error,
    errors,
    addingAddress,
    savingAddress,
    placing,
    fields,
    form,
    load,
    saveAddress,
    confirm,
  };
}
