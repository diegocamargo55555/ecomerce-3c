import { computed, onMounted, ref, watch } from "vue";
import { getCart } from "../requisicoes/carrinho.js";
import { listCatalog } from "../requisicoes/produtos.js";
import { listResource } from "../requisicoes/recursos.js";
import { available } from "../validacoes/produtos.js";

export function useCatalog(props, emit) {
  function orderCreated(order) {
    refreshCart();
    emit("navigate", `order/${order.id}`);
  }
  const products = ref([]);
  const categories = ref([]);
  const loading = ref(true);
  const productError = ref("");
  const categoryError = ref("");
  const search = ref("");
  const category = ref("");
  const sort = ref("newest");
  const page = ref(1);
  const pagination = ref({ total: 0, last_page: 1, from: null, to: null });
  const failedImages = ref(new Set());
  const cart = ref(null);
  const cartLoading = ref(true);
  const cartError = ref("");
  let cartVersion = 0;
  let loadVersion = 0;

  async function refreshCart() {
    const version = ++cartVersion;
    cartLoading.value = true;
    cartError.value = "";
    try {
      const value = await getCart();
      if (version === cartVersion) cart.value = value;
    } catch (failure) {
      if (version === cartVersion) cartError.value = failure.message;
    } finally {
      if (version === cartVersion) cartLoading.value = false;
    }
  }
  function updatedCart(value) {
    ++cartVersion;
    cart.value = value;
    cartLoading.value = false;
    cartError.value = "";
  }
  watch(() => props.user?.id, () => {
    cart.value = null;
    refreshCart();
  });
  const categoryNames = computed(
    () => new Map(categories.value.map((row) => [String(row.id), row.name])),
  );
  const error = computed(() =>
    props.section === "categories" ? categoryError.value : productError.value,
  );
  const totalPages = computed(() => pagination.value.last_page);

  function categoryName(id) {
    return (
      categoryNames.value.get(String(id)) ||
      (id == null ? "Sem categoria" : "Categoria indisponível")
    );
  }
  function chooseCategory(id = "") {
    page.value = 1;
    category.value = String(id);
    search.value = "";
    emit("navigate", "products");
  }
  function reset() {
    page.value = 1;
    category.value = "";
    search.value = "";
    sort.value = "newest";
    emit("navigate", "products");
  }
  async function load() {
    const version = ++loadVersion;
    loading.value = true;
    failedImages.value = new Set();
    const results = await Promise.allSettled([
      ["product", "cart", "checkout", "order", "my-orders", "profile"].includes(props.section)
        ? Promise.resolve({ data: [], meta: { total: 0, last_page: 1 } })
        : listCatalog({
            search: search.value,
            category: category.value,
            sort: sort.value,
            page: page.value,
          }),
      listResource("categories"),
    ]);
    if (version !== loadVersion) return;
    const [productResult, categoryResult] = results;
    products.value =
      productResult.status === "fulfilled" ? productResult.value.data : [];
    pagination.value =
      productResult.status === "fulfilled"
        ? productResult.value.meta
        : { total: 0, last_page: 1, from: null, to: null };
    categories.value =
      categoryResult.status === "fulfilled" ? categoryResult.value : [];
    productError.value =
      productResult.status === "rejected" ? productResult.reason.message : "";
    categoryError.value =
      categoryResult.status === "rejected" ? categoryResult.reason.message : "";
    loading.value = false;
  }
  watch([search, category, sort], () => {
    page.value = 1;
  });
  watch([search, category, sort, page], load);
  watch(
    () => props.section,
    (section, previous) => {
      if (["product", "cart", "checkout", "order", "my-orders", "profile"].includes(section) || ["product", "cart", "checkout", "order", "my-orders", "profile"].includes(previous)) load();
      if (section === "cart") refreshCart();
    },
  );
  onMounted(() => { load(); refreshCart(); });

  return {
    orderCreated,
    products,
    categories,
    loading,
    categoryError,
    search,
    category,
    sort,
    page,
    pagination,
    failedImages,
    cart,
    cartLoading,
    cartError,
    refreshCart,
    updatedCart,
    categoryNames,
    error,
    totalPages,
    categoryName,
    available,
    chooseCategory,
    reset,
    load,
  };
}
