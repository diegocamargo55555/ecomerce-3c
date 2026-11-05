<script setup>
import { nextTick, onMounted, reactive, ref, watch } from "vue";
import { apiData } from "@/api/http";
import { notify } from "@/stores/shop";
import { slugify } from "@/utils/format";
import UiState from "@/components/UiState.vue";
import AppField from "@/components/AppField.vue";
import AppIcon from "@/components/AppIcon.vue";
const categories = ref([]);
const loading = ref(true);
const error = ref("");
const editing = ref(false);
const saving = ref(false);
const removing = ref(null);
const formError = ref("");
const errors = ref({});
const form = reactive({ id: null, name: "", slug: "", description: "" });
watch(
  () => form.name,
  (name) => {
    if (!form.id) form.slug = slugify(name);
  },
);
async function load() {
  loading.value = true;
  error.value = "";
  try {
    categories.value = await apiData("/api/admin/categories");
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
async function edit(category) {
  Object.assign(
    form,
    category || { id: null, name: "", slug: "", description: "" },
  );
  form.description ||= "";
  errors.value = {};
  formError.value = "";
  editing.value = true;
  await nextTick();
  document.getElementById("category-name")?.focus();
}
async function save() {
  saving.value = true;
  errors.value = {};
  formError.value = "";
  try {
    const result = await apiData(
      `/api/admin/categories${form.id ? `/${form.id}` : ""}`,
      {
        method: form.id ? "PATCH" : "POST",
        body: {
          name: form.name,
          slug: form.slug,
          description: form.description || null,
        },
      },
    );
    categories.value = form.id
      ? categories.value.map((item) => (item.id === result.id ? result : item))
      : [...categories.value, result];
    editing.value = false;
    notify(form.id ? "Categoria atualizada." : "Categoria cadastrada.");
  } catch (failure) {
    formError.value = failure.message;
    errors.value = failure.errors || {};
  } finally {
    saving.value = false;
  }
}
async function remove(category) {
  if (!window.confirm(`Excluir a categoria “${category.name}”?`)) return;
  removing.value = category.id;
  try {
    await apiData(`/api/admin/categories/${category.id}`, { method: "DELETE" });
    categories.value = categories.value.filter(
      (item) => item.id !== category.id,
    );
    notify("Categoria excluída.");
  } catch (failure) {
    notify(failure.message, "error");
  } finally {
    removing.value = null;
  }
}
</script>
<template>
  <div>
    <div class="admin-page-heading">
      <div>
        <span class="eyebrow">ORGANIZAÇÃO</span>
        <h1>Categorias</h1>
        <p class="muted">Organize os produtos para facilitar a busca.</p>
      </div>
      <button
        class="btn btn-primary"
        :disabled="loading || !!error || saving"
        @click="edit()"
      >
        <AppIcon name="plus" :size="18" /> Nova categoria
      </button>
    </div>
    <UiState :loading="loading" :error="error" @retry="load"
      ><form v-if="editing" class="panel admin-form" @submit.prevent="save">
        <div class="panel-heading">
          <h2>{{ form.id ? "Editar categoria" : "Nova categoria" }}</h2>
          <button
            class="btn btn-ghost icon-btn"
            type="button"
            :disabled="saving"
            aria-label="Fechar formulário"
            @click="editing = false"
          >
            <AppIcon name="close" :size="18" />
          </button>
        </div>
        <p v-if="formError" class="alert alert-error" role="alert">
          {{ formError }}
        </p>
        <div class="form-grid">
          <AppField
            id="category-name"
            v-model="form.name"
            label="Nome da categoria"
            maxlength="255"
            required
            :error="errors.name"
          /><AppField
            v-model="form.slug"
            label="Identificador"
            placeholder="exemplo-de-categoria"
            maxlength="255"
            required
            pattern="[a-z0-9]+(-[a-z0-9]+)*"
            :error="errors.slug"
          />
          <div class="field span-two">
            <label for="category-description">Descrição (opcional)</label
            ><textarea
              id="category-description"
              v-model="form.description"
              rows="3"
            /><small v-if="errors.description" class="field-error">{{
              errors.description[0]
            }}</small>
          </div>
        </div>
        <div class="form-actions">
          <button
            class="btn btn-secondary"
            type="button"
            :disabled="saving"
            @click="editing = false"
          >
            Cancelar</button
          ><button class="btn btn-primary" :disabled="saving">
            {{ saving ? "Salvando..." : "Salvar categoria" }}
          </button>
        </div>
      </form>
      <UiState
        :empty="!categories.length"
        title="Organize suas primeiras escolhas"
        description="Adicione uma categoria para começar a cadastrar produtos."
        icon="tag"
        ><div class="table-panel">
          <div
            class="table-scroll"
            role="region"
            aria-label="Lista de categorias"
            tabindex="0"
          >
            <table>
              <thead>
                <tr>
                  <th>Categoria</th>
                  <th>Identificador</th>
                  <th>Descrição</th>
                  <th><span class="sr-only">Ações</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="category in categories" :key="category.id">
                  <td>
                    <strong>{{ category.name }}</strong>
                  </td>
                  <td>{{ category.slug }}</td>
                  <td>{{ category.description || "—" }}</td>
                  <td>
                    <div class="table-actions">
                      <button
                        class="btn btn-secondary btn-small"
                        :disabled="saving"
                        :aria-label="`Editar ${category.name}`"
                        @click="edit(category)"
                      >
                        Editar</button
                      ><button
                        class="text-button text-danger"
                        :disabled="removing !== null || saving"
                        :aria-label="`Excluir ${category.name}`"
                        @click="remove(category)"
                      >
                        {{
                          removing === category.id ? "Excluindo..." : "Excluir"
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
