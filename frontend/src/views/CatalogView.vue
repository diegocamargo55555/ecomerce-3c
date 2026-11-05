<script setup>
import { computed, ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { apiData } from "@/api/http";
import AppIcon from "@/components/AppIcon.vue";
import ProductCard from "@/components/ProductCard.vue";
import UiState from "@/components/UiState.vue";
const route = useRoute();
const router = useRouter();
const products = ref([]);
const categories = ref([]);
const loading = ref(true);
const error = ref("");
const sort = ref("recentes");
async function load() {
  loading.value = true;
  error.value = "";
  try {
    [products.value, categories.value] = await Promise.all([
      apiData("/api/products"),
      apiData("/api/categories"),
    ]);
  } catch (failure) {
    error.value = failure.message;
  } finally {
    loading.value = false;
  }
}
onMounted(load);
const filtered = computed(() => {
  const search = String(route.query.q || "").toLocaleLowerCase("pt-BR");
  const result = products.value.filter(
    (product) =>
      product.name.toLocaleLowerCase("pt-BR").includes(search) &&
      (!route.query.categoria ||
        String(product.category_id) === route.query.categoria),
  );
  if (sort.value === "menor")
    result.sort((a, b) => Number(a.price) - Number(b.price));
  if (sort.value === "maior")
    result.sort((a, b) => Number(b.price) - Number(a.price));
  return result;
});
const categoryName = (id) =>
  categories.value.find((category) => category.id === id)?.name;
function filter(id) {
  router.push({
    path: "/",
    query: { ...route.query, categoria: id ? String(id) : undefined },
  });
}
function reset() {
  sort.value = "recentes";
  router.push("/");
}
const categoryIcon = (name) =>
  /inform/i.test(name) ? "laptop" : /roup/i.test(name) ? "shirt" : "headphones";
</script>
<template>
  <div class="container catalog-page">
    <section class="hero">
      <div class="hero-copy">
        <span class="hero-kicker"><span /> ESCOLHAS QUE COMBINAM COM VOCÊ</span>
        <h1>Seu próximo<br />favorito está <em>aqui.</em></h1>
        <p>
          Tecnologia, estilo e praticidade.<br />Encontre o que faz sentido para
          o seu dia.
        </p>
        <a href="#produtos" class="btn btn-primary"
          >Explorar produtos <AppIcon name="arrow" :size="18"
        /></a>
      </div>
      <div class="hero-art" aria-hidden="true">
        <div class="hero-orbit orbit-one" />
        <div class="hero-orbit orbit-two" />
        <div class="hero-bag">
          <AppIcon name="bag" :size="110" /><span>3c</span>
        </div>
        <div class="floating-label label-heart">
          <AppIcon name="heart" :size="23" />
        </div>
        <div class="floating-label label-check">
          <AppIcon name="check" :size="17" /><span
            >Boas escolhas, todo dia.</span
          >
        </div>
        <span class="art-star star-one">✳︎</span
        ><span class="art-star star-two">+</span>
      </div>
    </section>
    <div class="benefits-strip">
      <span><AppIcon name="truck" /> Frete grátis</span
      ><span><AppIcon name="grid" /> Variedade para o dia a dia</span
      ><span><AppIcon name="bag" /> Tudo em um só lugar</span>
    </div>
    <section id="produtos" class="catalog-section">
      <div class="section-heading">
        <div>
          <span class="eyebrow">ENCONTRE O QUE É A SUA CARA</span>
          <h2>
            {{
              route.query.q
                ? `Resultados para “${route.query.q}”`
                : "Explore nossos produtos"
            }}
          </h2>
        </div>
        <span class="muted product-count" v-if="!loading && !error"
          >{{ filtered.length }}
          {{ filtered.length === 1 ? "produto" : "produtos" }}</span
        >
      </div>
      <UiState :loading="loading" :error="error" @retry="load"
        ><div class="catalog-controls">
          <div class="category-filters" aria-label="Filtrar por categoria">
            <button
              :class="['category-chip', { active: !route.query.categoria }]"
              :aria-pressed="!route.query.categoria"
              @click="filter()"
            >
              <AppIcon name="grid" :size="17" /> Todos</button
            ><button
              v-for="category in categories"
              :key="category.id"
              :class="[
                'category-chip',
                { active: route.query.categoria === String(category.id) },
              ]"
              :aria-pressed="route.query.categoria === String(category.id)"
              @click="filter(category.id)"
            >
              <AppIcon :name="categoryIcon(category.name)" :size="17" />{{
                category.name
              }}
            </button>
          </div>
          <label class="sort-control"
            ><span class="sr-only">Ordenar produtos</span
            ><select v-model="sort">
              <option value="recentes">Mais recentes</option>
              <option value="menor">Menor preço</option>
              <option value="maior">Maior preço</option>
            </select></label
          >
        </div>
        <UiState
          :empty="!filtered.length"
          title="Nenhum produto encontrado"
          description="Tente outra busca ou explore todas as categorias."
          icon="search"
          ><template #action
            ><button class="btn btn-primary" @click="reset">
              Ver todos os produtos
            </button></template
          >
          <div class="product-grid">
            <ProductCard
              v-for="product in filtered"
              :key="product.id"
              :product="product"
              :category="categoryName(product.category_id)"
            /></div
        ></UiState>
      </UiState>
    </section>
    <section class="catalog-bottom">
      <span class="bottom-icon"><AppIcon name="bag" :size="32" /></span>
      <div>
        <h2>Pequenas escolhas. Novas possibilidades.</h2>
        <p>Um catálogo pensado para acompanhar a sua rotina.</p>
      </div>
      <a href="#produtos" class="text-link"
        >Encontre seu favorito <AppIcon name="arrow" :size="18"
      /></a>
    </section>
  </div>
</template>
