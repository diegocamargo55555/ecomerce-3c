<script setup>
import { computed, onMounted, ref, watch } from "vue";
import { listCatalog, listResource } from "../api/http.js";
import { currency } from "../resources.js";
import Icon from "./Icon.vue";
import ProductView from "./ProductView.vue";

const props = defineProps({
  section: String,
  productId: String,
  user: Object,
  notice: String,
  signingOut: Boolean,
});
const emit = defineEmits(["navigate", "sign-out"]);
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
let loadVersion = 0;
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
function available(product) {
  return product.is_active && Number(product.stock) > 0;
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
    props.section === "product"
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
    if (section === "product" || previous === "product") load();
  },
);
onMounted(load);
</script>

<template>
  <div class="storefront">
    <a
      class="skip-link"
      href="#catalog-content"
      @click.prevent="$refs.content.focus()"
      >Pular para o conteúdo</a
    >
    <header class="store-header">
      <div class="store-container store-header-row">
        <a
          href="#products"
          class="brand store-brand"
          aria-label="3C Commerce, início"
          @click.prevent="reset"
        >
          <span class="brand-mark"><Icon name="bag" :size="25" /></span>
          <span
            >3c<span class="brand-light">commerce</span
            ><small>ESCOLHAS PARA O SEU DIA</small></span
          >
        </a>
        <form
          class="store-search"
          role="search"
          @submit.prevent="emit('navigate', 'products')"
        >
          <label class="sr-only" for="catalog-search">Buscar produtos</label>
          <input
            id="catalog-search"
            v-model="search"
            type="search"
            placeholder="O que você está procurando?"
          />
          <button type="submit" aria-label="Buscar">
            <Icon name="search" :size="21" />
          </button>
        </form>
        <div class="store-account">
          <a
            :href="
              user?.role === 'admin' ? '#overview' : user ? '#products' : '#login'
            "
            :aria-label="
              user?.role === 'admin'
                ? 'Acessar gestão'
                : user ? 'Sua conta' : 'Entrar ou criar conta'
            "
          >
            <Icon name="users" :size="23" />
            <span
              ><small>{{
                user ? `Olá, ${user.name.split(" ")[0]}` : "Sua conta"
              }}</small
              ><strong>{{
                user?.role === 'admin'
                  ? "Acessar gestão"
                  : user ? "Sua conta" : "Entre ou cadastre-se"
              }}</strong></span
            >
          </a>
          <button
            v-if="user"
            class="store-signout"
            :disabled="signingOut"
            aria-label="Sair da conta"
            @click="emit('sign-out')"
          >
            <span v-if="signingOut" class="spinner" /><Icon
              v-else
              name="logout"
              :size="20"
            />
          </button>
        </div>
      </div>
    </header>
    <nav class="store-navigation" aria-label="Categorias da loja">
      <div class="store-container store-nav-row">
        <a
          href="#categories"
          class="departments-link"
          :aria-current="section === 'categories' ? 'page' : undefined"
          ><Icon name="grid" :size="18" /> Categorias</a
        >
        <button
          :class="{ active: section === 'products' && !category }"
          :aria-pressed="section === 'products' && !category"
          @click="chooseCategory()"
        >
          Todos os produtos
        </button>
        <button
          v-for="item in categories"
          :key="item.id"
          :class="{
            active: section === 'products' && category === String(item.id),
          }"
          :aria-pressed="section === 'products' && category === String(item.id)"
          @click="chooseCategory(item.id)"
        >
          {{ item.name }}
        </button>
      </div>
    </nav>

    <main
      id="catalog-content"
      ref="content"
      class="store-container catalog-main"
      tabindex="-1"
      :aria-busy="loading"
    >
      <p v-if="notice" class="notice store-notice" role="status">
        {{ notice }}
      </p>
      <ProductView v-if="section === 'product'" :key="productId" :product-id="productId" />
      <template v-else>
      <header class="catalog-intro">
        <div>
          <span class="eyebrow">3C COMMERCE / CATÁLOGO</span>
          <h1>
            {{ section === "categories" ? "Categorias" : "Produtos"
            }}<span>.</span>
          </h1>
          <p>
            {{
              section === "categories"
                ? "Escolha uma categoria e explore os produtos."
                : "Encontre o que você precisa para o seu dia a dia."
            }}
          </p>
        </div>
        <a v-if="section === 'products'" href="#categories" class="text-button"
          >Explorar categorias <Icon name="arrow" :size="17"
        /></a>
        <a v-else href="#products" class="text-button" @click.prevent="reset"
          >Ver todos os produtos <Icon name="arrow" :size="17"
        /></a>
      </header>

      <div
        v-if="loading && !products.length"
        class="catalog-grid"
        aria-busy="true"
        role="status"
        aria-label="Carregando catálogo"
      >
        <div v-for="n in 4" :key="n" class="catalog-skeleton">
          <span class="skeleton" /><span class="skeleton" /><span
            class="skeleton"
          />
        </div>
      </div>
      <div v-else-if="error" class="panel empty-state" role="alert">
        <span class="empty-icon error-icon"
          ><Icon name="alert" :size="28"
        /></span>
        <h2>Não foi possível carregar o catálogo</h2>
        <p>{{ error }}</p>
        <button class="button primary" @click="load">
          <Icon name="refresh" :size="17" /> Tentar novamente
        </button>
      </div>
      <template v-else-if="section === 'categories'">
        <div v-if="categories.length" class="category-grid">
          <button
            v-for="item in categories"
            :key="item.id"
            class="category-card"
            @click="chooseCategory(item.id)"
          >
            <span class="category-icon"><Icon name="tag" :size="27" /></span>
            <strong>{{ item.name }}</strong
            ><span>{{
              item.description || "Explore os produtos desta categoria."
            }}</span>
            <span class="category-card-link"
              >Ver produtos <Icon name="right" :size="16"
            /></span>
          </button>
        </div>
        <div v-else class="panel empty-state">
          <Icon name="tag" :size="32" />
          <h2>Nenhuma categoria disponível</h2>
          <p>Volte em breve para conferir as novidades.</p>
        </div>
      </template>
      <template v-else>
        <div
          v-if="categoryError"
          class="notice catalog-reference-error"
          role="status"
        >
          <Icon name="alert" :size="18" /><span
            >As categorias estão temporariamente indisponíveis.</span
          ><button class="text-button" @click="load">Tentar novamente</button>
        </div>
        <div class="catalog-toolbar">
          <div class="catalog-results" role="status" aria-live="polite">
            <h2>{{ categoryNames.get(category) || "Todos os produtos" }}</h2>
            <span
              >{{ pagination.total }}
              {{
                pagination.total === 1
                  ? "produto encontrado"
                  : "produtos encontrados"
              }}</span
            >
          </div>
          <label class="catalog-sort"
            >Ordenar por
            <select v-model="sort" aria-label="Ordenar produtos">
              <option value="newest">Mais recentes</option>
              <option value="price-asc">Menor preço</option>
              <option value="price-desc">Maior preço</option>
              <option value="name">Nome: A–Z</option>
            </select>
          </label>
        </div>
        <div v-if="!products.length" class="panel empty-state">
          <span class="empty-icon"
            ><Icon :name="search || category ? 'search' : 'box'" :size="28"
          /></span>
          <h2>
            {{
              search || category
                ? "Nenhum produto encontrado"
                : "Nenhum produto disponível"
            }}
          </h2>
          <p>
            {{
              search || category
                ? "Tente outra busca ou escolha outra categoria."
                : "Volte em breve para conferir as novidades."
            }}
          </p>
          <button
            v-if="search || category"
            class="button secondary"
            @click="reset"
          >
            Limpar filtros
          </button>
        </div>
        <div v-else class="catalog-grid">
          <article
            v-for="product in products"
            :key="product.id"
            class="product-card"
          >
            <a
              class="catalog-product-image"
              :aria-label="`Ver detalhes de ${product.name}`"
              :href="`#products/${product.id}`"
            >
              <img
                v-if="product.image_url && !failedImages.has(product.id)"
                :src="product.image_url"
                :alt="product.name"
                loading="lazy"
                @error="failedImages.add(product.id)"
              />
              <span v-else class="catalog-image-placeholder"
                ><Icon name="box" :size="48" /><small>Sem imagem</small></span
              >
            </a>
            <div class="product-card-body">
              <span class="product-card-category">{{
                categoryName(product.category_id)
              }}</span>
              <h3>
                <a :title="product.name" :href="`#products/${product.id}`">
                  {{ product.name }}
                </a>
              </h3>
              <p class="product-card-description">{{ product.description }}</p>
              <span
                :class="[
                  'product-availability',
                  { unavailable: !available(product) },
                ]"
                ><i />{{
                  available(product) ? "Disponível" : "Indisponível"
                }}</span
              >
              <strong class="catalog-price">{{
                currency(product.price)
              }}</strong>
              <a
                class="button catalog-details-button"
                :aria-label="`Detalhes de ${product.name}`"
                :href="`#products/${product.id}`"
              >
                Ver detalhes <Icon name="right" :size="17" />
              </a>
            </div>
          </article>
        </div>
        <nav
          v-if="pagination.total && products.length"
          class="catalog-pagination"
          aria-label="Páginas de produtos"
        >
          <span
            >Mostrando {{ pagination.from }}–{{ pagination.to }}
            de {{ pagination.total }}</span
          >
          <div class="pagination">
            <button
              class="icon-button bordered"
              aria-label="Página anterior"
              :disabled="loading || page === 1"
              @click="page--"
            >
              <Icon name="left" :size="17" /></button
            ><span>{{ page }} / {{ totalPages }}</span
            ><button
              class="icon-button bordered"
              aria-label="Próxima página"
              :disabled="loading || page === totalPages"
              @click="page++"
            >
              <Icon name="right" :size="17" />
            </button>
          </div>
        </nav>
      </template>
      </template>
    </main>

    <footer class="store-footer">
      <div class="store-container store-footer-row">
        <span
          ><strong>3c<span>commerce</span></strong
          ><small>O essencial para o seu dia.</small></span
        >
        <nav aria-label="Links do rodapé">
          <a href="#products">Produtos</a><a href="#categories">Categorias</a
          ><a href="#overview">Gestão da loja</a>
        </nav>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.storefront {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
}
.storefront .skip-link {
  left: 20px;
}
.store-container {
  width: min(1240px, calc(100% - 48px));
  margin-inline: auto;
}
.store-header {
  background: var(--primary);
  color: white;
}
.store-header-row {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr) auto;
  align-items: center;
  gap: 40px;
  padding-block: 24px;
}
.store-brand {
  color: white;
}
.store-header :is(a, button):focus-visible {
  outline-color: white;
}
.store-brand .brand-mark {
  background: var(--accent);
  color: #17212b;
  border: 0;
  border-radius: 8px;
}
.store-brand small {
  color: #e1efff;
  font-size: 7px;
  letter-spacing: 1.4px;
}
.store-search {
  display: flex;
  background: white;
  border-radius: 7px;
  overflow: hidden;
  min-width: 0;
}
.store-search:focus-within {
  outline: 2px solid white;
  outline-offset: 4px;
}
.store-search input {
  width: 100%;
  min-width: 0;
  height: 46px;
  border: 0;
  box-shadow: none;
  padding: 12px 16px;
  font-size: 14px;
  color: var(--text);
  background: white;
}
.store-search button {
  display: grid;
  place-items: center;
  width: 50px;
  flex-shrink: 0;
  background: var(--accent-dark);
  color: white;
  border: 0;
}
.store-search button:hover {
  background: #9a3412;
}
.store-search button:focus-visible {
  outline-offset: -4px;
  outline-color: white;
}
.store-account,
.store-account a {
  display: flex;
  align-items: center;
  gap: 12px;
}
.store-account a:hover strong {
  text-decoration: underline;
}
.store-account small,
.store-account strong {
  display: block;
}
.store-account small {
  font-size: 11px;
  color: #e1efff;
  margin-bottom: 2px;
}
.store-account strong {
  font-size: 12px;
  font-weight: 600;
}
.store-signout {
  border: 0;
  background: transparent;
  color: white;
  padding: 9px;
  border-radius: 6px;
}
.store-navigation {
  background: white;
  border-top: 3px solid var(--accent);
  border-bottom: 1px solid var(--border);
}
.store-nav-row {
  display: flex;
  align-items: stretch;
  gap: 28px;
  overflow-x: auto;
  scrollbar-width: thin;
}
.store-nav-row a,
.store-nav-row button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  gap: 9px;
  padding: 16px 0;
  border: 0;
  border-bottom: 3px solid transparent;
  background: transparent;
  color: var(--muted);
  font-size: 13px;
  white-space: nowrap;
}
.store-nav-row .departments-link {
  color: var(--primary);
  font-weight: 700;
  padding-right: 26px;
  border-right: 1px solid var(--border);
}
.store-nav-row .active,
.store-nav-row [aria-current="page"] {
  color: var(--primary);
  border-bottom-color: var(--accent);
  font-weight: 600;
}
.store-nav-row a:hover,
.store-nav-row button:hover {
  color: var(--primary);
}
.store-nav-row :focus-visible {
  outline-offset: -4px;
}
.catalog-main {
  padding-block: 38px 48px;
}
.store-notice {
  margin-bottom: 24px;
}
.catalog-intro {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding-bottom: 30px;
  margin-bottom: 26px;
  border-bottom: 1px solid var(--border);
}
.catalog-intro .eyebrow {
  color: var(--primary);
  font-size: 10px;
  letter-spacing: 1.4px;
}
.catalog-intro h1 {
  font-size: 40px;
  font-weight: 800;
  letter-spacing: -1.5px;
}
.catalog-intro h1 > span {
  color: var(--accent-dark);
}
.catalog-intro p {
  color: var(--muted);
  margin-top: 10px;
  font-size: 14px;
}
.catalog-intro .text-button {
  font-size: 13px;
}
.catalog-reference-error {
  margin-bottom: 24px;
}
.catalog-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 20px;
}
.catalog-results {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px 12px;
}
.catalog-results h2 {
  font-size: 18px;
}
.catalog-results > span {
  font-size: 12px;
  color: var(--muted);
}
.catalog-sort {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
  color: var(--muted);
  font-size: 12px;
}
.catalog-sort select {
  padding: 9px 10px;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: white;
  color: var(--text);
  font-size: 13px;
}
.catalog-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 20px;
}
.product-card {
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
  transition:
    border-color 0.15s,
    box-shadow 0.15s;
}
.product-card:hover,
.product-card:focus-within {
  border-color: #9bb9d6;
  box-shadow: 0 5px 18px #17212b08;
}
.catalog-product-image {
  display: grid;
  place-items: center;
  width: 100%;
  aspect-ratio: 4 / 3;
  min-height: 0;
  padding: 24px;
  border: 0;
  background: #f8fafc;
  overflow: hidden;
}
.catalog-product-image:focus-visible {
  outline-offset: -4px;
}
.catalog-product-image img {
  display: block;
  width: 100%;
  height: 100%;
  min-height: 0;
  object-fit: contain;
}
.catalog-image-placeholder {
  display: flex;
  align-items: center;
  flex-direction: column;
  gap: 12px;
  color: var(--muted);
}
.catalog-image-placeholder > svg {
  color: #91a9c0;
  stroke-width: 1.1;
}
.catalog-image-placeholder small {
  font-size: 11px;
}
.product-card-body {
  display: flex;
  flex-direction: column;
  flex: 1;
  padding: 20px;
}
.product-card-category {
  color: var(--primary);
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.7px;
}
.product-card h3 {
  margin-top: 8px;
  font-size: 16px;
  letter-spacing: -0.2px;
  line-height: 1.5;
}
.product-card h3 a {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 48px;
  border: 0;
  background: transparent;
  padding: 0;
  font: inherit;
  font-weight: 650;
  text-align: left;
  overflow-wrap: anywhere;
}
.product-card h3 a:hover {
  color: var(--primary);
}
.product-card-description {
  font-size: 12px;
  color: var(--muted);
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  min-height: 18px;
  margin: 6px 0 14px;
}
.product-availability {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: var(--primary);
  font-size: 11px;
}
.product-availability i {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}
.product-availability.unavailable {
  color: var(--muted);
}
.catalog-price {
  display: block;
  color: var(--accent-dark);
  font-size: 25px;
  font-weight: 800;
  letter-spacing: -0.8px;
  margin: 12px 0 18px;
}
.catalog-details-button {
  width: 100%;
  margin-top: auto;
  justify-content: space-between;
  background: white;
  border-color: var(--accent-dark);
  color: var(--accent-dark);
  border-radius: 6px;
}
.catalog-details-button:hover {
  background: var(--accent-dark);
  color: white;
}
.catalog-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 28px;
  padding-top: 22px;
  border-top: 1px solid var(--border);
  color: var(--muted);
  font-size: 12px;
}
.catalog-pagination .pagination {
  font-size: 12px;
}
.catalog-skeleton {
  padding: 20px;
  background: white;
  border: 1px solid var(--border);
  border-radius: 10px;
}
.catalog-skeleton .skeleton {
  display: block;
  height: 18px;
  width: 75%;
  margin-bottom: 20px;
}
.catalog-skeleton .skeleton:first-child {
  height: 170px;
  width: 100%;
}
.category-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}
.category-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  text-align: left;
  gap: 14px;
  padding: 28px;
  border: 1px solid var(--border);
  border-radius: 10px;
  background: white;
}
.category-card:hover {
  border-color: var(--primary);
}
.category-icon {
  display: grid;
  place-items: center;
  width: 52px;
  height: 52px;
  background: #eaf3fc;
  color: var(--primary);
  border-radius: 10px;
}
.category-card > strong {
  font-size: 19px;
}
.category-card > span:not(.category-icon):not(.category-card-link) {
  font-size: 13px;
  color: var(--muted);
}
.category-card-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  color: var(--accent-dark);
  font-size: 12px;
  font-weight: 600;
  margin-top: auto;
  padding-top: 14px;
}
.store-footer {
  border-top: 1px solid var(--border);
  background: white;
}
.store-footer-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding-block: 28px;
}
.store-footer strong {
  font-family: "Manrope", sans-serif;
  font-size: 19px;
  letter-spacing: -0.8px;
}
.store-footer strong > span {
  font-weight: 400;
  margin-left: 3px;
}
.store-footer small {
  display: block;
  font-size: 11px;
  color: var(--muted);
  margin-top: 5px;
}
.store-footer nav {
  display: flex;
  flex-wrap: wrap;
  gap: 14px 24px;
  font-size: 12px;
  color: var(--muted);
}
.store-footer a:hover {
  color: var(--primary);
  text-decoration: underline;
}
@media (max-width: 1199px) {
  .catalog-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .store-header-row {
    gap: 24px;
  }
}
@media (max-width: 899px) {
  .store-header-row {
    grid-template-columns: 1fr auto;
    gap: 20px;
    padding-block: 20px;
  }
  .store-search {
    order: 1;
    grid-column: 1 / -1;
  }
  .catalog-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .category-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 599px) {
  .store-container {
    width: calc(100% - 32px);
  }
  .store-brand {
    font-size: 23px;
  }
  .store-brand .brand-light {
    font-size: 16px;
  }
  .store-brand .brand-mark {
    width: 34px;
    height: 38px;
  }
  .store-brand small {
    font-size: 6px;
    letter-spacing: 1px;
  }
  .store-account a {
    gap: 7px;
  }
  .store-account strong {
    font-size: 10px;
  }
  .store-account small {
    font-size: 10px;
  }
  .store-account > a > svg {
    width: 19px;
  }
  .store-account:has(.store-signout) a > span {
    display: none;
  }
  .store-search input {
    font-size: 16px;
    padding-left: 12px;
  }
  .store-nav-row {
    gap: 20px;
  }
  .store-nav-row a,
  .store-nav-row button {
    font-size: 12px;
    padding-block: 14px;
  }
  .store-nav-row .departments-link {
    padding-right: 18px;
  }
  .catalog-main {
    padding-block: 26px 36px;
  }
  .catalog-intro {
    display: block;
    padding-bottom: 24px;
    margin-bottom: 22px;
  }
  .catalog-intro h1 {
    font-size: 32px;
  }
  .catalog-intro p {
    font-size: 13px;
  }
  .catalog-intro .text-button {
    margin-top: 16px;
    font-size: 12px;
  }
  .catalog-toolbar {
    align-items: flex-start;
    flex-direction: column;
  }
  .catalog-sort {
    justify-content: space-between;
    width: 100%;
  }
  .catalog-sort select {
    flex: 1;
    max-width: 175px;
  }
  .catalog-grid {
    gap: 12px;
  }
  .catalog-product-image {
    padding: 16px;
  }
  .catalog-image-placeholder > svg {
    width: 32px;
    height: 32px;
  }
  .catalog-image-placeholder {
    gap: 6px;
  }
  .catalog-image-placeholder small {
    font-size: 10px;
  }
  .product-card-body {
    padding: 13px;
  }
  .product-card-category {
    font-size: 9px;
    letter-spacing: 0.3px;
  }
  .product-card h3 {
    font-size: 14px;
  }
  .product-card h3 a {
    min-height: 42px;
  }
  .product-card-description {
    font-size: 11px;
    margin-bottom: 12px;
  }
  .product-availability {
    font-size: 10px;
  }
  .catalog-price {
    font-size: 21px;
    letter-spacing: -0.7px;
    margin-bottom: 14px;
  }
  .catalog-details-button {
    font-size: 11px;
    padding: 9px 10px;
  }
  .catalog-pagination {
    font-size: 11px;
  }
  .category-grid {
    grid-template-columns: 1fr;
    gap: 14px;
  }
  .store-footer-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 20px;
  }
  .store-footer nav {
    gap: 20px;
    font-size: 11px;
  }
}
@media (max-width: 359px) {
  .catalog-grid {
    grid-template-columns: 1fr;
  }
  .store-account a > span {
    display: none;
  }
}
</style>
