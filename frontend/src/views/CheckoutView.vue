<script setup>
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { apiData } from "@/api/http";
import {
  cart,
  cartCount,
  cartTotal,
  invalidCart,
  loadCart,
  notify,
  session,
} from "@/stores/shop";
import UiState from "@/components/UiState.vue";
import AppField from "@/components/AppField.vue";
import AppIcon from "@/components/AppIcon.vue";
import CartSummary from "@/components/CartSummary.vue";
const router = useRouter();
const addresses = ref([]);
const selected = ref(null);
const adding = ref(false);
const loading = ref(true);
const error = ref("");
const formError = ref("");
const errors = ref({});
const saving = ref(false);
const confirming = ref(false);
const fields = reactive({
  recipient_name: session.user?.name || "",
  zip_code: "",
  street: "",
  number: "",
  complement: "",
  neighborhood: "",
  city: "",
  state: "",
});
const states =
  "AC AL AP AM BA CE DF ES GO MA MT MS MG PA PB PR PE PI RJ RN RS RO RR SC SP SE TO".split(
    " ",
  );
async function load() {
  loading.value = true;
  error.value = "";
  try {
    const [data] = await Promise.all([apiData("/api/addresses"), loadCart()]);
    addresses.value = data;
    selected.value = data[0]?.id || null;
    adding.value = !data.length;
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
async function save() {
  saving.value = true;
  formError.value = "";
  errors.value = {};
  try {
    const address = await apiData("/api/addresses", {
      method: "POST",
      body: fields,
    });
    addresses.value.push(address);
    selected.value = address.id;
    adding.value = false;
    notify("Endereço cadastrado.");
  } catch (failure) {
    formError.value = failure.message;
    errors.value = failure.errors || {};
  } finally {
    saving.value = false;
  }
}
async function confirm() {
  if (!selected.value || confirming.value) return;
  confirming.value = true;
  formError.value = "";
  try {
    const order = await apiData("/api/checkout", {
      method: "POST",
      body: { address_id: selected.value },
    });
    cart.items = [];
    notify("Pedido realizado com sucesso!");
    await router.replace({ path: "/pedidos", query: { novo: order.id } });
  } catch (failure) {
    formError.value = failure.message;
    try {
      await loadCart();
    } catch {
      /* Preserve the checkout error. */
    }
  } finally {
    confirming.value = false;
  }
}
</script>
<template>
  <div class="container page-section">
    <RouterLink to="/carrinho" class="back-link"
      ><AppIcon name="back" :size="17" /> Voltar ao carrinho</RouterLink
    >
    <div class="page-heading">
      <span class="eyebrow">QUASE LÁ</span>
      <h1>Finalizar pedido<span class="heading-dot">.</span></h1>
      <p class="muted">Confira o endereço e confirme suas escolhas.</p>
    </div>
    <UiState
      :loading="loading"
      :error="error"
      :empty="!cart.items.length"
      title="Adicione produtos para continuar"
      description="Seu carrinho está vazio. Escolha seus favoritos no catálogo."
      @retry="load"
      ><template #action
        ><RouterLink class="btn btn-primary" to="/"
          >Explorar produtos</RouterLink
        ></template
      >
      <p v-if="formError" class="alert alert-error" role="alert">
        {{ formError }}
      </p>
      <p v-if="invalidCart" class="alert alert-error">
        Algum produto ficou indisponível.
        <RouterLink to="/carrinho">Revise seu carrinho.</RouterLink>
      </p>
      <div class="shopping-layout">
        <div class="checkout-main">
          <section class="panel">
            <div class="panel-heading">
              <h2><span class="step-number">1</span> Endereço de entrega</h2>
              <button
                v-if="addresses.length && !adding"
                class="text-button"
                @click="adding = true"
              >
                <AppIcon name="plus" :size="16" /> Novo endereço
              </button>
            </div>
            <div v-if="!adding" class="address-options">
              <label
                v-for="address in addresses"
                :key="address.id"
                class="address-option"
                :class="{ selected: selected === address.id }"
                ><input
                  v-model="selected"
                  type="radio"
                  name="address"
                  :value="address.id"
                /><span
                  ><strong>{{ address.recipient_name }}</strong
                  ><span
                    >{{ address.street }}, {{ address.number
                    }}<template v-if="address.complement">
                      · {{ address.complement }}</template
                    ></span
                  ><span
                    >{{ address.neighborhood }} · {{ address.city }}/{{
                      address.state
                    }}</span
                  ><span>CEP {{ address.zip_code }}</span></span
                ></label
              >
            </div>
            <form v-else @submit.prevent="save">
              <div class="form-grid">
                <AppField
                  v-model="fields.recipient_name"
                  class="span-two"
                  label="Nome do destinatário"
                  autocomplete="shipping name"
                  required
                  maxlength="255"
                  :error="errors.recipient_name"
                /><AppField
                  v-model="fields.zip_code"
                  label="CEP"
                  autocomplete="shipping postal-code"
                  placeholder="00000-000"
                  inputmode="numeric"
                  pattern="[0-9]{5}-?[0-9]{3}"
                  required
                  :error="errors.zip_code"
                /><AppField
                  v-model="fields.street"
                  class="span-two"
                  label="Rua / Avenida"
                  autocomplete="shipping address-line1"
                  required
                  maxlength="255"
                  :error="errors.street"
                /><AppField
                  v-model="fields.number"
                  label="Número"
                  required
                  maxlength="30"
                  :error="errors.number"
                /><AppField
                  v-model="fields.complement"
                  label="Complemento (opcional)"
                  maxlength="255"
                  :error="errors.complement"
                /><AppField
                  v-model="fields.neighborhood"
                  label="Bairro"
                  required
                  maxlength="255"
                  :error="errors.neighborhood"
                /><AppField
                  v-model="fields.city"
                  label="Cidade"
                  autocomplete="shipping address-level2"
                  required
                  maxlength="255"
                  :error="errors.city"
                />
                <div class="field">
                  <label for="address-state">Estado</label
                  ><select
                    id="address-state"
                    v-model="fields.state"
                    required
                    autocomplete="shipping address-level1"
                  >
                    <option disabled value="">Selecione</option>
                    <option v-for="state in states" :key="state">
                      {{ state }}
                    </option></select
                  ><small v-if="errors.state" class="field-error">{{
                    errors.state[0]
                  }}</small>
                </div>
              </div>
              <div class="form-actions">
                <button
                  v-if="addresses.length"
                  type="button"
                  class="btn btn-secondary"
                  :disabled="saving"
                  @click="adding = false"
                >
                  Cancelar</button
                ><button class="btn btn-primary" :disabled="saving">
                  {{ saving ? "Salvando..." : "Usar este endereço" }}
                </button>
              </div>
            </form>
          </section>
          <section class="panel">
            <h2><span class="step-number">2</span> Confirmação</h2>
            <p class="muted">
              Seu pedido será registrado como pendente. Esta versão não realiza
              cobranças; o status é atualizado pela administração.
            </p>
          </section>
        </div>
        <CartSummary :count="cartCount" :total="cartTotal"
          ><button
            class="btn btn-primary full-width"
            :disabled="
              !selected || adding || confirming || invalidCart || cart.busy
            "
            @click="confirm"
          >
            {{ confirming ? "Confirmando..." : "Confirmar pedido"
            }}<AppIcon v-if="!confirming" name="check" :size="18" /></button
        ></CartSummary></div
    ></UiState>
  </div>
</template>
