<script setup>
import { nextTick, onMounted, reactive, ref } from "vue";
import { apiData } from "@/api/http";
import { notify } from "@/stores/shop";
import { money } from "@/utils/format";
import AppIcon from "@/components/AppIcon.vue";
import AppField from "@/components/AppField.vue";
import UiState from "@/components/UiState.vue";
import ProductImage from "@/components/ProductImage.vue";
const products = ref([]);
const categories = ref([]);
const loading = ref(true);
const error = ref("");
const editing = ref(false);
const saving = ref(false);
const changing = ref(null);
const formError = ref("");
const errors = ref({});
const blank = () => ({
  id: null,
  name: "",
  category_id: "",
  description: "",
  price: "",
  stock: 0,
  image_url: "",
  is_active: true,
});
const form = reactive(blank());
async function load() {
  loading.value = true;
  error.value = "";
  try {
    [products.value, categories.value] = await Promise.all([
      apiData("/api/admin/products"),
      apiData("/api/admin/categories"),
    ]);
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
async function edit(product) {
  Object.assign(form, blank(), product || {});
  form.image_url ||= "";
  errors.value = {};
  formError.value = "";
  editing.value = true;
  await nextTick();
  document.getElementById("product-name")?.focus();
}
async function save() {
  saving.value = true;
  errors.value = {};
  formError.value = "";
  try {
    const body = {
      name: form.name,
      category_id: Number(form.category_id),
      description: form.description,
      price: Number(form.price),
      stock: Number(form.stock),
      image_url: form.image_url || null,
      is_active: form.is_active,
    };
    const product = await apiData(
      `/api/admin/products${form.id ? `/${form.id}` : ""}`,
      { method: form.id ? "PATCH" : "POST", body },
    );
    products.value = form.id
      ? products.value.map((item) => (item.id === product.id ? product : item))
      : [product, ...products.value];
    editing.value = false;
    notify(form.id ? "Produto atualizado." : "Produto cadastrado.");
  } catch (failure) {
    formError.value = failure.message;
    errors.value = failure.errors || {};
  } finally {
    saving.value = false;
  }
}
async function toggle(product) {
  changing.value = product.id;
  try {
    const updated = await apiData(`/api/admin/products/${product.id}`, {
      method: "PATCH",
      body: { is_active: !product.is_active },
    });
    products.value = products.value.map((item) =>
      item.id === updated.id ? updated : item,
    );
    notify(updated.is_active ? "Produto ativado." : "Produto desativado.");
  } catch (failure) {
    notify(failure.message, "error");
  } finally {
    changing.value = null;
  }
}
const categoryName = (id) =>
  categories.value.find((category) => category.id === id)?.name || "—";
</script>
<template>
  <div>
    <div class="admin-page-heading">
      <div>
        <span class="eyebrow">CATÁLOGO</span>
        <h1>Produtos</h1>
        <p class="muted">Cuide das escolhas disponíveis na sua loja.</p>
      </div>
      <button
        class="btn btn-primary"
        :disabled="loading || !!error || saving"
        @click="edit()"
      >
        <AppIcon name="plus" :size="18" /> Novo produto
      </button>
    </div>
    <UiState :loading="loading" :error="error" @retry="load"
      ><form v-if="editing" class="panel admin-form" @submit.prevent="save">
        <div class="panel-heading">
          <h2>{{ form.id ? "Editar produto" : "Novo produto" }}</h2>
          <button
            class="icon-btn btn btn-ghost"
            type="button"
            aria-label="Fechar formulário"
            :disabled="saving"
            @click="editing = false"
          >
            <AppIcon name="close" :size="18" />
          </button>
        </div>
        <p v-if="formError" class="alert alert-error" role="alert">
          {{ formError }}
        </p>
        <p v-if="!categories.length" class="alert alert-info">
          Cadastre uma
          <RouterLink to="/admin/categorias">categoria</RouterLink> antes de
          adicionar produtos.
        </p>
        <div class="form-grid">
          <AppField
            id="product-name"
            v-model="form.name"
            label="Nome do produto"
            required
            maxlength="255"
            :error="errors.name"
          />
          <div class="field">
            <label for="product-category">Categoria</label
            ><select
              id="product-category"
              v-model="form.category_id"
              required
              :aria-invalid="errors.category_id ? true : undefined"
            >
              <option disabled value="">Selecione uma categoria</option>
              <option
                v-for="category in categories"
                :key="category.id"
                :value="category.id"
              >
                {{ category.name }}
              </option></select
            ><small v-if="errors.category_id" class="field-error">{{
              errors.category_id[0]
            }}</small>
          </div>
          <div class="field span-two">
            <label for="product-description">Descrição</label
            ><textarea
              id="product-description"
              v-model="form.description"
              required
              rows="3"
              :aria-invalid="errors.description ? true : undefined"
            /><small v-if="errors.description" class="field-error">{{
              errors.description[0]
            }}</small>
          </div>
          <AppField
            v-model="form.price"
            label="Preço (R$)"
            type="number"
            min="0"
            max="99999999.99"
            step="0.01"
            required
            :error="errors.price"
          /><AppField
            v-model="form.stock"
            label="Estoque"
            type="number"
            min="0"
            max="2147483647"
            step="1"
            required
            :error="errors.stock"
          /><AppField
            v-model="form.image_url"
            class="span-two"
            label="URL da imagem (opcional)"
            type="url"
            maxlength="255"
            placeholder="https://exemplo.com/produto.jpg"
            :error="errors.image_url"
          /><label class="checkbox-field span-two"
            ><input v-model="form.is_active" type="checkbox" /> Produto ativo no
            catálogo</label
          >
        </div>
        <div class="form-actions">
          <button
            type="button"
            class="btn btn-secondary"
            :disabled="saving"
            @click="editing = false"
          >
            Cancelar</button
          ><button
            class="btn btn-primary"
            :disabled="saving || !categories.length"
          >
            {{ saving ? "Salvando..." : "Salvar produto" }}
          </button>
        </div>
      </form>
      <UiState
        :empty="!products.length"
        title="Seu catálogo começa aqui"
        description="Cadastre o primeiro produto da sua loja."
        icon="box"
        ><div class="table-panel">
          <div
            class="table-scroll"
            role="region"
            aria-label="Lista de produtos"
            tabindex="0"
          >
            <table>
              <thead>
                <tr>
                  <th>Produto</th>
                  <th>Categoria</th>
                  <th>Preço</th>
                  <th>Estoque</th>
                  <th>Status</th>
                  <th><span class="sr-only">Ações</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in products" :key="product.id">
                  <td>
                    <div class="table-product">
                      <ProductImage :product="product" /><strong>{{
                        product.name
                      }}</strong>
                    </div>
                  </td>
                  <td>{{ categoryName(product.category_id) }}</td>
                  <td class="nowrap">{{ money(product.price) }}</td>
                  <td>{{ product.stock }}</td>
                  <td>
                    <span
                      class="badge"
                      :class="
                        product.is_active ? 'badge-pago' : 'badge-cancelado'
                      "
                      >{{ product.is_active ? "Ativo" : "Inativo" }}</span
                    >
                  </td>
                  <td>
                    <div class="table-actions">
                      <button
                        class="btn btn-secondary btn-small"
                        :disabled="saving"
                        :aria-label="`Editar ${product.name}`"
                        @click="edit(product)"
                      >
                        Editar</button
                      ><button
                        class="text-button"
                        :disabled="changing !== null || saving"
                        @click="toggle(product)"
                      >
                        {{
                          changing === product.id
                            ? "Aguarde..."
                            : product.is_active
                              ? "Desativar"
                              : "Ativar"
                        }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div></UiState
      ></UiState
    >
  </div>
</template>
