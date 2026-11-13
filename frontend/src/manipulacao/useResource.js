import { computed, reactive, ref, watch } from "vue";
import { deleteResource, listResource, saveResource } from "../requisicoes/recursos.js";
import { uploadProductImage } from "../requisicoes/produtos.js";
import { exceedsFileLimit } from "../validacoes/arquivos.js";
import { resources } from "../resources.js";
import { currency, date, relationLabel, statusNames } from "./formatacao.js";

export function useResource(props, emit) {
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
  let originalForm = {};

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
    if (column.format === "status") return statusNames[value] || value;
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
    // shortcut: loads whole tables, because search, filters and relation labels run in the browser; move them to the server with paginate() when a table passes a few thousand rows.
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
      if (field.type === "file") value = null;
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
    originalForm = { ...form };
    formError.value = "";
    fieldErrors.value = {};
    modalOpen.value = true;
  }

  function closeForm() {
    if (!busy.value) modalOpen.value = false;
  }

  function chooseFile(field, event) {
    const file = event.target.files?.[0] || null;
    const errors = { ...fieldErrors.value };
    delete errors[field.key];
    if (exceedsFileLimit(file, field)) {
      errors[field.key] = ["O arquivo ultrapassa o limite de 2 MB."];
      event.target.value = "";
      form[field.key] = null;
    } else {
      form[field.key] = file;
    }
    fieldErrors.value = errors;
  }

  async function save() {
    busy.value = true;
    formError.value = "";
    fieldErrors.value = {};
    const payload = {};
    const image = props.resourceKey === "products" ? form.image : null;
    let savedBeforeUpload = false;
    for (const field of fields.value) {
      if (field.type === "file") continue;
      const value = form[field.key];
      if (editing.value && value === originalForm[field.key]) continue;
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
      // Com arquivo escolhido, a URL atual não é reenviada: o upload a substitui.
      if (image && id) delete payload.image_url;
      const saved = await saveResource(props.resourceKey, id, payload);
      originalForm = { ...form };
      if (image) {
        // O produto já foi salvo: se o upload falhar, o formulário passa a
        // editá-lo, e uma nova tentativa não cria um produto duplicado.
        editing.value = { ...saved, ...payload, id: saved.id };
        savedBeforeUpload = true;
        await uploadProductImage(saved.id, image);
      }
      if (props.resourceKey === "users" && id === props.user.id)
        emit("user-updated", saved);
      modalOpen.value = false;
      emit("notify", `Registro ${id ? "atualizado" : "criado"} com sucesso.`);
      await load();
    } catch (failure) {
      formError.value = savedBeforeUpload
        ? `O produto foi salvo, mas a imagem não foi enviada. ${failure.message}`
        : failure.message;
      fieldErrors.value = failure.errors || {};
      if (savedBeforeUpload) load();
    } finally {
      busy.value = false;
    }
  }

  async function remove() {
    busy.value = true;
    formError.value = "";
    try {
      const id = deleting.value.id;
      await deleteResource(props.resourceKey, id);
      deleting.value = null;
      emit("notify", "Registro excluído com sucesso.");
      await load();
    } catch (failure) {
      formError.value =
        failure.status === 409 && props.resourceKey === "categories"
          ? "Não é possível excluir esta categoria: há produtos vinculados a ela. Mova ou exclua os produtos antes."
          : failure.status === 409 && props.resourceKey === "users"
          ? "Não é possível excluir esta pessoa: é a sua própria conta ou há pedidos registrados nela."
          : failure.status >= 500
          ? "Não foi possível excluir. Este registro pode estar vinculado a outros dados da loja."
          : failure.message;
    } finally {
      busy.value = false;
    }
  }

  return {
    config,
    rows,
    lookups,
    loading,
    error,
    referenceError,
    search,
    filter,
    sort,
    page,
    pageSize,
    editing,
    modalOpen,
    deleting,
    busy,
    form,
    formError,
    fieldErrors,
    failedImages,
    fields,
    tabs,
    cell,
    filtered,
    totalPages,
    visibleRows,
    load,
    openForm,
    closeForm,
    chooseFile,
    save,
    remove,
  };
}
