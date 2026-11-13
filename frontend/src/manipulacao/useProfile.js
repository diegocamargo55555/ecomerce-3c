import { computed, nextTick, onMounted, reactive, ref } from "vue";
import { createAddress, deleteAddress, getAddresses, updateAddress, updateProfile } from "../requisicoes/perfil.js";
import { resources } from "../resources.js";

export function useProfile(props, emit) {
  const profile = reactive({ name: props.user.name, email: props.user.email });
  const currentPassword = ref("");
  const changingEmail = computed(() => profile.email !== props.user.email);
  const savingProfile = ref(false);
  const profileError = ref("");
  const profileErrors = ref({});
  const notice = ref("");
  const addresses = ref([]);
  const addressesTitle = ref(null);
  const loading = ref(true);
  const loadError = ref("");
  const editing = ref(null);
  const deleting = ref(null);
  const busy = ref(false);
  const addressError = ref("");
  const addressErrors = ref({});
  const fields = resources.addresses.fields.filter(field => field.key !== "users_id");
  const form = reactive(Object.fromEntries(fields.map(field => [field.key, ""])));

  async function loadAddresses() {
    loading.value = true;
    loadError.value = "";
    try {
      addresses.value = await getAddresses();
    } catch (failure) {
      loadError.value = failure.message;
    } finally {
      loading.value = false;
    }
  }
  async function saveProfile() {
    if (savingProfile.value) return;
    savingProfile.value = true;
    profileError.value = "";
    profileErrors.value = {};
    notice.value = "";
    try {
      const user = await updateProfile({ ...profile, ...(changingEmail.value ? { current_password: currentPassword.value } : {}) });
      emit("updated", user);
      profile.name = user.name;
      profile.email = user.email;
      notice.value = "Perfil atualizado.";
    } catch (failure) {
      profileError.value = failure.message;
      profileErrors.value = failure.errors;
    } finally {
      currentPassword.value = "";
      savingProfile.value = false;
    }
  }
  function editAddress(address = {}) {
    fields.forEach(field => { form[field.key] = address[field.key] ?? ""; });
    addressError.value = "";
    addressErrors.value = {};
    editing.value = address;
  }
  function confirmDelete(address) {
    addressError.value = "";
    deleting.value = address;
  }
  async function saveAddress() {
    if (busy.value) return;
    busy.value = true;
    addressError.value = "";
    addressErrors.value = {};
    try {
      const originalId = editing.value.id;
      const address = originalId ? await updateAddress(originalId, form) : await createAddress(form);
      if (originalId) addresses.value = addresses.value.map(item => item.id === originalId ? address : item);
      else addresses.value.unshift(address);
      editing.value = null;
      notice.value = "Endereço salvo. Os endereços dos pedidos anteriores foram preservados.";
      await nextTick();
      addressesTitle.value?.focus();
    } catch (failure) {
      addressError.value = failure.message;
      addressErrors.value = failure.errors;
      if (failure.status === 404) { editing.value = null; await loadAddresses(); notice.value = failure.message; }
    } finally {
      busy.value = false;
    }
  }
  async function removeAddress() {
    if (busy.value) return;
    busy.value = true;
    addressError.value = "";
    try {
      await deleteAddress(deleting.value.id);
      addresses.value = addresses.value.filter(item => item.id !== deleting.value.id);
      deleting.value = null;
      notice.value = "Endereço excluído. Os pedidos anteriores foram preservados.";
      await nextTick();
      addressesTitle.value?.focus();
    } catch (failure) {
      addressError.value = failure.message;
      if (failure.status === 404) { deleting.value = null; await loadAddresses(); notice.value = failure.message; }
    } finally {
      busy.value = false;
    }
  }
  onMounted(loadAddresses);

  return {
    profile,
    currentPassword,
    changingEmail,
    savingProfile,
    profileError,
    profileErrors,
    notice,
    addresses,
    addressesTitle,
    loading,
    loadError,
    editing,
    deleting,
    busy,
    addressError,
    addressErrors,
    fields,
    form,
    loadAddresses,
    saveProfile,
    editAddress,
    confirmDelete,
    saveAddress,
    removeAddress,
  };
}
