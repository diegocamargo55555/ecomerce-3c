<script setup>
import { computed, reactive, ref, watch } from "vue";
import { api, listResource } from "../api/http.js";
import {
  resources,
  currency,
  date,
  initials,
  relationLabel,
} from "../resources.js";
import Icon from "./Icon.vue";
import Modal from "./Modal.vue";

const props = defineProps({ resourceKey: String, user: Object });
const emit = defineEmits([
  "notify",
  "navigate",
  "user-updated",
  "self-deleted",
]);
const config = computed(() => resources[props.resourceKey]);
const rows = ref([]);
const lookups = ref({});
const loading = ref(true);
const error = ref("");
const referenceError = ref("");
const search = ref("");
const filter = ref("all");
const sort = ref("newest");
const page = ref(1);
const pageSize = 8;
const editing = ref(null);
const modalOpen = ref(false);
const deleting = ref(null);
const busy = ref(false);
const form = reactive({});
const formError = ref("");
const fieldErrors = ref({});
const failedImages = ref(new Set());
let loadVersion = 0;

const fields = computed(() =>
  config.value.fields.filter((field) => !editing.value || !field.createOnly),
);
const tabs = computed(() => {
  const parent = config.value.parent || props.resourceKey;
  return resources[parent].related ? [parent, resources[parent].related] : [];
});

function labelFor(key, id) {
  const item = lookups.value[key]?.find((row) => Number(row.id) === Number(id));
  return item
    ? relationLabel(key, item)
    : id == null
      ? "Visitante"
      : `#${String(id).padStart(4, "0")}`;
}

function cell(row, column) {
  const value = row[column.key];
  if (column.format === "money") return currency(value);
  if (column.format === "date") return date(value);
  if (column.format === "id") return `#${String(value).padStart(4, "0")}`;
  if (column.format === "active") return value ? "Ativo" : "Inativo";
  if (column.format === "status")
    return (
      { pendente: "Pendente", pago: "Pago", cancelado: "Cancelado" }[value] ||
      value
    );
  if (resources[column.format]) return labelFor(column.format, value);
  return value ?? "—";
}

const filtered = computed(() => {
  const query = search.value.trim().toLocaleLowerCase("pt-BR");
  return rows.value
    .filter((row) => {
      const matches =
        !query ||
        config.value.columns.some((column) =>
          String(cell(row, column)).toLocaleLowerCase("pt-BR").includes(query),
        );
      if (!matches) return false;
      if (filter.value === "active") return !!row.is_active;
      if (filter.value === "low") return Number(row.stock) <= 5;
      if (filter.value !== "all") return row.status === filter.value;
      return true;
    })
    .sort((a, b) =>
      sort.value === "name"
        ? String(
            a.name || a.product_name || a.recipient_name || a.id,
          ).localeCompare(
            String(b.name || b.product_name || b.recipient_name || b.id),
            "pt-BR",
            { numeric: true },
          )
        : Number(b.id) - Number(a.id),
    );
});
const totalPages = computed(() =>
  Math.max(1, Math.ceil(filtered.value.length / pageSize)),
);
const visibleRows = computed(() =>
  filtered.value.slice((page.value - 1) * pageSize, page.value * pageSize),
);
watch([search, filter, sort], () => {
  page.value = 1;
});
watch(totalPages, (value) => {
  page.value = Math.min(page.value, value);
});

async function load() {
  const version = ++loadVersion;
  const key = props.resourceKey;
  loading.value = true;
  error.value = "";
  referenceError.value = "";
  const keys = [
    ...new Set(
      config.value.fields
        .filter((field) => field.resource)
        .map((field) => field.resource),
    ),
  ];
  const result = await Promise.allSettled([
    listResource(key),
    ...keys.map(listResource),
  ]);
  if (version !== loadVersion) return;
  const [main, ...references] = result;
  if (main.status === "fulfilled") rows.value = main.value;
  else {
    error.value = main.reason.message;
    rows.value = [];
  }
  lookups.value = Object.fromEntries(
    references.map((result, index) => [
      keys[index],
      result.status === "fulfilled" ? result.value : [],
    ]),
  );
  if (references.some((result) => result.status === "rejected"))
    referenceError.value =
      "Algumas referências não puderam ser carregadas. Atualize antes de preencher os relacionamentos.";
  loading.value = false;
}

watch(
  () => props.resourceKey,
  () => {
    search.value = "";
    filter.value = "all";
    page.value = 1;
    modalOpen.value = false;
    deleting.value = null;
    rows.value = [];
    failedImages.value = new Set();
    load();
  },
  { immediate: true },
);

function openForm(row = null) {
  editing.value = row;
  Object.keys(form).forEach((key) => delete form[key]);
  for (const field of config.value.fields) {
    let value = row?.[field.key] ?? field.default ?? "";
    if (field.type === "password") value = "";
    if (field.type === "checkbox")
      value = row ? !!row[field.key] : !!field.default;
    if (field.type === "datetime-local" && value) {
      const local = new Date(value);
      value = Number.isNaN(local.getTime())
        ? ""
        : new Date(local.getTime() - local.getTimezoneOffset() * 60000)
            .toISOString()
            .slice(0, 16);
    }
    form[field.key] = value;
  }
  if (!row && ["users_id", "user_id"].some((key) => key in form)) {
    const key = "users_id" in form ? "users_id" : "user_id";
    form[key] = props.user.id;
  }
  formError.value = "";
  fieldErrors.value = {};
  modalOpen.value = true;
}

function closeForm() {
  if (!busy.value) modalOpen.value = false;
}

async function save() {
  busy.value = true;
  formError.value = "";
  fieldErrors.value = {};
  const payload = {};
  for (const field of fields.value) {
    const value = form[field.key];
    if (editing.value && field.optionalOnEdit && !value) continue;
    payload[field.key] =
      value === "" && !field.required
        ? null
        : field.type === "number" || field.resource
          ? Number(value)
          : typeof value === "string" && field.type !== "password"
            ? value.trim()
            : value;
    if (field.type === "datetime-local" && value)
      payload[field.key] = new Date(value).toISOString();
  }
  try {
    const id = editing.value?.id;
    await api(`/api/${props.resourceKey}${id ? `/${id}` : ""}`, {
      method: id ? "PATCH" : "POST",
      body: payload,
    });
    if (props.resourceKey === "users" && id === props.user.id)
      emit("user-updated", {
        ...props.user,
        name: payload.name,
        email: payload.email,
      });
    modalOpen.value = false;
    emit("notify", `Registro ${id ? "atualizado" : "criado"} com sucesso.`);
    await load();
  } catch (failure) {
    formError.value = failure.message;
    fieldErrors.value = failure.errors || {};
  } finally {
    busy.value = false;
  }
}

async function remove() {
  busy.value = true;
  formError.value = "";
  try {
    const id = deleting.value.id;
    await api(`/api/${props.resourceKey}/${id}`, { method: "DELETE" });
    deleting.value = null;
    if (props.resourceKey === "users" && id === props.user.id) {
      emit("self-deleted");
      return;
    }
    emit("notify", "Registro excluído com sucesso.");
    await load();
  } catch (failure) {
    formError.value =
      failure.status >= 500
        ? "Não foi possível excluir. Este registro pode estar vinculado a outros dados da loja."
        : failure.message;
  } finally {
    busy.value = false;
  }
}

function exportCsv() {
  const escape = (value) =>
    `"${String(value ?? "")
      .replace(/^[=+@\-\t\r]/, "'$&")
      .replaceAll('"', '""')}"`;
  const data = [
    config.value.columns.map((column) => escape(column.label)).join(";"),
    ...filtered.value.map((row) =>
      config.value.columns.map((column) => escape(cell(row, column))).join(";"),
    ),
  ].join("\r\n");
  const url = URL.createObjectURL(
    new Blob(["\uFEFF" + data], { type: "text/csv;charset=utf-8;" }),
  );
  const anchor = document.createElement("a");
  anchor.href = url;
  anchor.download = `${props.resourceKey}.csv`;
  anchor.click();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}
</script>

<template>
  <section class="page-content">
    <header class="page-heading">
      <div>
        <span class="eyebrow">{{
          ["products", "categories"].includes(resourceKey)
            ? "SEU CATÁLOGO"
            : "SUA OPERAÇÃO"
        }}</span>
        <h1>{{ config.title }}</h1>
        <p class="muted">{{ config.description }}</p>
      </div>
      <button
        v-if="user"
        class="button primary"
        :disabled="loading || !!error"
        @click="openForm()"
      >
        <Icon name="plus" :size="18" />
        {{ ["categories", "users"].includes(resourceKey) ? "Nova" : "Novo" }}
        {{ config.singular }}
      </button>
    </header>
    <nav v-if="tabs.length" class="resource-tabs" aria-label="Seção">
      <button
        v-for="tab in tabs"
        :key="tab"
        :class="{ active: tab === resourceKey }"
        @click="emit('navigate', tab)"
      >
        {{ resources[tab].title }}
      </button>
    </nav>
    <div
      v-if="referenceError && !error"
      class="notice reference-notice"
      role="status"
    >
      <Icon name="alert" :size="18" /><span>{{ referenceError }}</span
      ><button class="text-button" @click="load">Atualizar</button>
    </div>
    <div class="panel data-panel" :aria-busy="loading">
      <div class="table-toolbar">
        <div class="list-tabs" aria-label="Filtrar registros">
          <button :class="{ active: filter === 'all' }" @click="filter = 'all'">
            Todos <span class="count">{{ rows.length }}</span></button
          ><template v-if="resourceKey === 'products'"
            ><button
              :class="{ active: filter === 'active' }"
              @click="filter = 'active'"
            >
              Ativos</button
            ><button
              :class="{ active: filter === 'low' }"
              @click="filter = 'low'"
            >
              Estoque baixo
            </button></template
          ><template v-if="['orders', 'payments'].includes(resourceKey)"
            ><button
              :class="{ active: filter === 'pendente' }"
              @click="filter = 'pendente'"
            >
              Pendentes</button
            ><button
              :class="{ active: filter === 'pago' }"
              @click="filter = 'pago'"
            >
              Pagos</button
            ><button
              :class="{ active: filter === 'cancelado' }"
              @click="filter = 'cancelado'"
            >
              Cancelados
            </button></template
          >
        </div>
        <button
          class="button subtle export-button"
          :disabled="loading || !filtered.length"
          @click="exportCsv"
        >
          <Icon name="download" :size="16" /> Exportar
        </button>
      </div>
      <div class="search-toolbar">
        <div class="search-input">
          <Icon name="search" :size="18" /><input
            v-model="search"
            type="search"
            :aria-label="`Buscar ${config.title.toLowerCase()}`"
            :placeholder="`Buscar ${config.title.toLowerCase()}…`"
            :disabled="loading"
          />
        </div>
        <div class="table-tools">
          <label class="sort-select"
            ><Icon name="sort" :size="16" /><select
              v-model="sort"
              aria-label="Ordenar registros"
            >
              <option value="newest">Mais recentes</option>
              <option value="name">Ordem crescente</option>
            </select></label
          ><button
            class="icon-button bordered"
            aria-label="Atualizar lista"
            :disabled="loading"
            @click="load"
          >
            <Icon name="refresh" :size="17" />
          </button>
        </div>
      </div>
      <div v-if="loading" class="table-loading" role="status">
        <div v-for="n in 5" :key="n" class="skeleton-row">
          <span class="skeleton square"></span><span class="skeleton"></span
          ><span class="skeleton"></span><span class="skeleton short"></span>
        </div>
        <span class="sr-only"
          >Carregando {{ config.title.toLowerCase() }}…</span
        >
      </div>
      <div v-else-if="error" class="empty-state" role="alert">
        <span class="empty-icon error-icon"
          ><Icon name="alert" :size="27"
        /></span>
        <h2>Não foi possível carregar</h2>
        <p>{{ error }}</p>
        <button class="button secondary" @click="load">
          <Icon name="refresh" :size="16" /> Tentar novamente
        </button>
      </div>
      <div v-else-if="!visibleRows.length" class="empty-state">
        <span class="empty-icon"
          ><Icon
            :name="search || filter !== 'all' ? 'search' : config.icon"
            :size="28"
        /></span>
        <h2>
          {{
            search || filter !== "all"
              ? "Nenhum resultado por aqui"
              : user
                ? "Seu primeiro registro começa aqui"
                : "Nenhum registro disponível"
          }}
        </h2>
        <p>
          {{
            search || filter !== "all"
              ? "Tente outra busca ou ajuste os filtros."
              : user
                ? `Adicione um novo registro para começar a organizar ${config.title.toLowerCase()}.`
                : "Volte em breve para conferir as novidades."
          }}
        </p>
        <button
          v-if="search || filter !== 'all'"
          class="button secondary"
          @click="
            search = '';
            filter = 'all';
          "
        >
          Limpar filtros</button
        ><button v-else-if="user" class="button secondary" @click="openForm()">
          <Icon name="plus" :size="16" /> Adicionar {{ config.singular }}
        </button>
      </div>
      <div v-else class="table-scroll">
        <table>
          <caption class="sr-only">
            {{
              config.title
            }}
          </caption>
          <thead>
            <tr>
              <th
                v-for="column in config.columns"
                :key="column.key"
                scope="col"
              >
                {{ column.label }}
              </th>
              <th v-if="user" scope="col" class="actions-heading">Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in visibleRows" :key="row.id">
              <td
                v-for="column in config.columns"
                :key="column.key"
                :class="{
                  'strong-cell': ['strong', 'id'].includes(column.format),
                }"
              >
                <div v-if="column.format === 'product'" class="product-cell">
                  <div class="product-thumb">
                    <img
                      v-if="row.image_url && !failedImages.has(row.id)"
                      :src="row.image_url"
                      :alt="row.name"
                      loading="lazy"
                      @error="failedImages.add(row.id)"
                    /><Icon v-else name="box" :size="23" />
                  </div>
                  <div>
                    <span class="product-name">{{ row.name }}</span
                    ><small>#{{ String(row.id).padStart(4, "0") }}</small>
                  </div>
                </div>
                <div v-else-if="column.format === 'person'" class="person-cell">
                  <span class="avatar small">{{ initials(row.name) }}</span
                  ><strong>{{ row.name }}</strong>
                </div>
                <span
                  v-else-if="column.format === 'active'"
                  :class="['badge', row.is_active ? 'success' : 'neutral']"
                  ><i />{{ cell(row, column) }}</span
                ><span
                  v-else-if="column.format === 'status'"
                  :class="[
                    'badge',
                    row.status === 'pago'
                      ? 'success'
                      : row.status === 'cancelado'
                        ? 'neutral'
                        : 'warning',
                  ]"
                  ><i />{{ cell(row, column) }}</span
                ><span
                  v-else-if="column.format === 'stock'"
                  :class="['stock-value', { low: Number(row.stock) <= 5 }]"
                  ><i v-if="Number(row.stock) <= 5" />{{ row.stock }}
                  <small>un.</small></span
                ><span
                  v-else
                  class="cell-text"
                  :title="String(cell(row, column))"
                  >{{ cell(row, column) }}</span
                >
              </td>
              <td v-if="user" class="row-actions">
                <button
                  class="icon-button"
                  :aria-label="`Editar ${config.singular} ${row.name || row.id}`"
                  title="Editar"
                  @click="openForm(row)"
                >
                  <Icon name="edit" :size="17" /></button
                ><button
                  class="icon-button danger-hover"
                  :aria-label="`Excluir ${config.singular} ${row.name || row.id}`"
                  title="Excluir"
                  @click="
                    deleting = row;
                    formError = '';
                  "
                >
                  <Icon name="trash" :size="17" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <footer v-if="!loading && !error && filtered.length" class="table-footer">
        <span
          >Mostrando
          <strong
            >{{ (page - 1) * pageSize + 1 }}–{{
              Math.min(page * pageSize, filtered.length)
            }}</strong
          >
          de <strong>{{ filtered.length }}</strong> registros</span
        >
        <div class="pagination">
          <button
            class="icon-button bordered"
            aria-label="Página anterior"
            :disabled="page === 1"
            @click="page--"
          >
            <Icon name="left" :size="16" /></button
          ><span
            >{{ page }} <span class="muted">/ {{ totalPages }}</span></span
          ><button
            class="icon-button bordered"
            aria-label="Próxima página"
            :disabled="page === totalPages"
            @click="page++"
          >
            <Icon name="right" :size="16" />
          </button>
        </div>
      </footer>
    </div>
    <p class="page-footnote">Uma loja organizada abre espaço para crescer.</p>
    <Modal
      v-if="modalOpen && user"
      :title="`${editing ? 'Editar' : ['categories', 'users'].includes(resourceKey) ? 'Nova' : 'Novo'} ${config.singular}`"
      :busy="busy"
      @close="closeForm"
      ><form @submit.prevent="save">
        <div class="modal-body">
          <div v-if="formError" class="alert" role="alert">
            <Icon name="alert" :size="18" /><span>{{ formError }}</span>
          </div>
          <div v-if="referenceError" class="notice">{{ referenceError }}</div>
          <div class="form-grid">
            <div
              v-for="field in fields"
              :key="field.key"
              :class="[
                'field',
                { wide: field.wide || field.type === 'checkbox' },
              ]"
            >
              <template v-if="field.type === 'checkbox'"
                ><label class="checkbox-label"
                  ><input
                    :id="`field-${field.key}`"
                    v-model="form[field.key]"
                    type="checkbox"
                    :disabled="busy"
                    :aria-invalid="!!fieldErrors[field.key]"
                  />{{ field.label }}</label
                ></template
              ><template v-else
                ><label :for="`field-${field.key}`"
                  >{{ field.label }}
                  <span
                    v-if="!field.required || (editing && field.optionalOnEdit)"
                    class="optional"
                    >(opcional)</span
                  ></label
                ><textarea
                  v-if="field.type === 'textarea'"
                  :id="`field-${field.key}`"
                  v-model="form[field.key]"
                  rows="3"
                  :required="field.required"
                  :disabled="busy"
                  :aria-invalid="!!fieldErrors[field.key]"
                  :aria-describedby="
                    fieldErrors[field.key] ? `error-${field.key}` : undefined
                  "
                /><select
                  v-else-if="field.type === 'select'"
                  :id="`field-${field.key}`"
                  v-model="form[field.key]"
                  :required="field.required"
                  :disabled="busy || (field.resource && !!referenceError)"
                  :aria-invalid="!!fieldErrors[field.key]"
                  :aria-describedby="
                    fieldErrors[field.key] ? `error-${field.key}` : undefined
                  "
                >
                  <option :value="field.required ? '' : ''">
                    {{
                      field.resource && !lookups[field.resource]?.length
                        ? "Nenhum registro disponível"
                        : "Selecione…"
                    }}
                  </option>
                  <template v-if="field.resource"
                    ><option
                      v-for="option in lookups[field.resource] || []"
                      :key="option.id"
                      :value="option.id"
                    >
                      {{ relationLabel(field.resource, option) }}
                    </option></template
                  ><template v-else
                    ><option
                      v-for="option in field.options"
                      :key="option[0]"
                      :value="option[0]"
                    >
                      {{ option[1] }}
                    </option></template
                  ></select
                ><input
                  v-else
                  :id="`field-${field.key}`"
                  v-model="form[field.key]"
                  :type="field.type"
                  :required="
                    field.required && !(editing && field.optionalOnEdit)
                  "
                  :min="field.min"
                  :max="field.type === 'number' ? field.max : undefined"
                  :maxlength="field.type !== 'number' ? field.max : undefined"
                  :minlength="field.minLength"
                  :step="field.step"
                  :autocomplete="
                    field.type === 'password' ? 'new-password' : undefined
                  "
                  :disabled="busy"
                  :aria-invalid="!!fieldErrors[field.key]"
                  :aria-describedby="
                    fieldErrors[field.key] ? `error-${field.key}` : undefined
                  "
                /><small v-if="field.hint" class="muted">{{ field.hint }}</small
                ><small
                  v-if="
                    field.resource &&
                    !lookups[field.resource]?.length &&
                    !referenceError
                  "
                  class="muted"
                  >Cadastre
                  {{ resources[field.resource].title.toLowerCase() }} para
                  selecionar aqui.</small
                ></template
              ><span
                v-if="fieldErrors[field.key]"
                :id="`error-${field.key}`"
                class="field-error"
                >{{ fieldErrors[field.key][0] }}</span
              >
            </div>
          </div>
        </div>
        <footer class="modal-footer">
          <button
            type="button"
            class="button secondary"
            :disabled="busy"
            @click="closeForm"
          >
            Cancelar</button
          ><button
            type="submit"
            class="button primary"
            :disabled="busy || !!referenceError"
          >
            <span v-if="busy" class="spinner" /><Icon
              v-else
              name="check"
              :size="17"
            />{{ busy ? "Salvando…" : "Salvar registro" }}
          </button>
        </footer>
      </form></Modal
    >
    <Modal
      v-if="deleting && user"
      title="Excluir registro?"
      :busy="busy"
      small
      @close="deleting = null"
      ><div class="modal-body">
        <span class="empty-icon error-icon delete-icon"
          ><Icon name="trash" :size="25"
        /></span>
        <p>
          Você está prestes a excluir
          <strong>{{
            deleting.name ||
            deleting.product_name ||
            deleting.recipient_name ||
            `o registro #${deleting.id}`
          }}</strong
          >. Esta ação não pode ser desfeita.
        </p>
        <p
          v-if="resourceKey === 'users' && deleting.id === user.id"
          class="notice"
        >
          Este é o seu usuário. Sua conta e seu acesso serão removidos.
        </p>
        <div v-if="formError" class="alert" role="alert">{{ formError }}</div>
      </div>
      <footer class="modal-footer">
        <button
          class="button secondary"
          :disabled="busy"
          @click="deleting = null"
        >
          Cancelar</button
        ><button class="button danger" :disabled="busy" @click="remove">
          <span v-if="busy" class="spinner" />{{
            busy ? "Excluindo…" : "Sim, excluir"
          }}
        </button>
      </footer></Modal
    >
  </section>
</template>
