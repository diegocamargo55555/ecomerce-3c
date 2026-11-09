<script setup>
import { computed, nextTick, onMounted, reactive, ref } from "vue";
import { createAddress, deleteAddress, getAddresses, updateAddress, updateProfile } from "../api/http.js";
import { resources } from "../resources.js";
import Modal from "./Modal.vue";
import Icon from "./Icon.vue";

const props = defineProps({ user: Object });
const emit = defineEmits(["updated"]);
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
</script>

<template>
  <section class="profile-page">
    <header><span class="eyebrow">3C COMMERCE / SUA CONTA</span><h1>Meu perfil</h1></header>
    <p v-if="notice" class="notice" role="status">{{ notice }}</p>
    <form class="panel profile-panel" @submit.prevent="saveProfile" :aria-busy="savingProfile">
      <h2>Meus dados</h2>
      <p v-if="profileError" class="alert" role="alert">{{ profileError }}</p>
      <div class="form-grid">
        <div v-for="field in [{ key: 'name', label: 'Nome completo', type: 'text', autocomplete: 'name' }, { key: 'email', label: 'E-mail', type: 'email', autocomplete: 'email' }]" :key="field.key" class="field">
          <label :for="`profile-${field.key}`">{{ field.label }}</label>
          <input :id="`profile-${field.key}`" v-model="profile[field.key]" :type="field.type" :autocomplete="field.autocomplete" required maxlength="255" :disabled="savingProfile"
            :aria-invalid="!!profileErrors[field.key]" :aria-describedby="profileErrors[field.key] ? `profile-error-${field.key}` : undefined" />
          <span v-if="profileErrors[field.key]" :id="`profile-error-${field.key}`" class="field-error">{{ profileErrors[field.key][0] }}</span>
        </div>
        <div v-if="changingEmail" class="field">
          <label for="profile-current-password">Senha atual para alterar o e-mail</label>
          <input id="profile-current-password" v-model="currentPassword" type="password" autocomplete="current-password" required :disabled="savingProfile"
            :aria-invalid="!!profileErrors.current_password" :aria-describedby="profileErrors.current_password ? 'profile-error-password' : undefined" />
          <span v-if="profileErrors.current_password" id="profile-error-password" class="field-error">{{ profileErrors.current_password[0] }}</span>
        </div>
      </div>
      <div><button type="submit" class="button primary" :disabled="savingProfile">{{ savingProfile ? "Salvando…" : "Salvar perfil" }}</button></div>
    </form>
    <section class="profile-addresses" :aria-busy="loading || busy">
      <header class="address-heading"><h2 ref="addressesTitle" tabindex="-1">Meus endereços</h2><button type="button" class="button secondary" :disabled="loading || busy" @click="editAddress()"><Icon name="plus" :size="16" /> Cadastrar endereço</button></header>
      <div v-if="loading" class="panel empty-state" role="status"><span class="spinner" /> Carregando endereços…</div>
      <div v-else-if="loadError" class="panel empty-state" role="alert"><p>{{ loadError }}</p><button class="button secondary" @click="loadAddresses">Tentar novamente</button></div>
      <p v-else-if="!addresses.length" class="panel profile-panel">Você ainda não tem endereços cadastrados.</p>
      <ul v-else class="address-list">
        <li v-for="address in addresses" :key="address.id" class="panel address-row">
          <address><strong>{{ address.recipient_name }}</strong><br />{{ address.street }}, {{ address.number }}<template v-if="address.complement"> · {{ address.complement }}</template><br />{{ address.neighborhood }} · {{ address.city }}/{{ address.state }}<br />CEP {{ address.zip_code }}</address>
          <div class="address-actions"><button class="button secondary" :aria-label="`Editar endereço ${address.street}, ${address.number}`" :disabled="busy" @click="editAddress(address)">Editar</button><button class="button danger" :aria-label="`Excluir endereço ${address.street}, ${address.number}`" :disabled="busy" @click="confirmDelete(address)">Excluir</button></div>
        </li>
      </ul>
    </section>
    <Modal v-if="editing" :title="editing.id ? 'Editar endereço' : 'Cadastrar endereço'" :busy="busy" @close="editing = null">
      <form @submit.prevent="saveAddress">
        <div class="modal-body">
          <p class="muted">Alterações valem para novas compras. Os pedidos anteriores manterão o endereço original.</p>
          <p v-if="addressError" class="alert" role="alert">{{ addressError }}</p>
          <div class="form-grid">
            <div v-for="field in fields" :key="field.key" class="field">
              <label :for="`address-${field.key}`">{{ field.label }}<span v-if="!field.required" class="optional"> (opcional)</span></label>
              <input :id="`address-${field.key}`" v-model="form[field.key]" type="text" :required="field.required" :maxlength="field.max" :disabled="busy"
                :aria-invalid="!!addressErrors[field.key]" :aria-describedby="addressErrors[field.key] ? `address-error-${field.key}` : undefined" />
              <span v-if="addressErrors[field.key]" :id="`address-error-${field.key}`" class="field-error">{{ addressErrors[field.key][0] }}</span>
            </div>
          </div>
        </div>
        <footer class="modal-footer"><button type="button" class="button secondary" :disabled="busy" @click="editing = null">Cancelar</button><button type="submit" class="button primary" :disabled="busy">{{ busy ? "Salvando…" : "Salvar endereço" }}</button></footer>
      </form>
    </Modal>
    <Modal v-if="deleting" title="Excluir endereço?" :busy="busy" small @close="deleting = null">
      <div class="modal-body"><p>Excluir {{ deleting.street }}, {{ deleting.number }} da sua lista de endereços? Os pedidos anteriores serão preservados.</p><p v-if="addressError" class="alert" role="alert">{{ addressError }}</p></div>
      <footer class="modal-footer"><button class="button secondary" :disabled="busy" @click="deleting = null">Manter endereço</button><button class="button danger" :disabled="busy" @click="removeAddress">{{ busy ? "Excluindo…" : "Confirmar exclusão" }}</button></footer>
    </Modal>
  </section>
</template>

<style scoped>
.profile-page, .profile-panel, .profile-addresses { display: grid; gap: 24px; }
.profile-panel { padding: 26px; }
.address-heading, .address-row, .address-actions { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.address-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 16px; }
.address-row { padding: 24px; }
.address-row address { font-style: normal; line-height: 1.8; overflow-wrap: anywhere; min-width: 0; }
@media (max-width: 500px) { .profile-panel, .address-row { padding: 18px; } .address-row { align-items: flex-start; flex-direction: column; } }
</style>
