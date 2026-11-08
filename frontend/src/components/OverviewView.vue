<script setup>
import { computed, onMounted, ref } from "vue";
import { listResource } from "../api/http.js";
import { currency, date } from "../resources.js";
import Icon from "./Icon.vue";

defineProps({ user: Object });
const emit = defineEmits(["navigate"]);
const data = ref({ products: [], orders: [], payments: [], users: [] });
const loading = ref(true);
const error = ref("");
const keys = ["products", "orders", "payments", "users"];
async function load() {
  loading.value = true;
  error.value = "";
  const results = await Promise.allSettled(keys.map(listResource));
  results.forEach((result, index) => {
    data.value[keys[index]] =
      result.status === "fulfilled" ? result.value : null;
  });
  if (results.some((result) => result.status === "rejected"))
    error.value =
      "Alguns dados não puderam ser carregados. Tente atualizar o painel.";
  loading.value = false;
}
onMounted(load);
const lowStock = computed(() =>
  (data.value.products || [])
    .filter((row) => row.is_active && Number(row.stock) <= 5)
    .sort((a, b) => a.stock - b.stock),
);
const recentOrders = computed(() =>
  [...(data.value.orders || [])].sort((a, b) => b.id - a.id).slice(0, 5),
);
const stats = computed(() => [
  {
    title: "Total de pedidos",
    value: data.value.orders?.length ?? "—",
    icon: "orders",
    caption: "Pedidos registrados",
    target: "orders",
  },
  {
    title: "Produtos ativos",
    value: data.value.products?.filter((row) => row.is_active).length ?? "—",
    icon: "box",
    caption: "Disponíveis no catálogo",
    target: "products",
  },
  {
    title: "Pagamentos recebidos",
    value: data.value.payments
      ? currency(
          data.value.payments
            .filter((row) => row.status === "pago")
            .reduce((sum, row) => sum + Number(row.amount), 0),
        )
      : "—",
    icon: "wallet",
    caption: "Total com status pago",
    target: "payments",
  },
  {
    title: "Atenção ao estoque",
    value: data.value.products ? lowStock.value.length : "—",
    icon: "alert",
    caption: "Produtos com até 5 unidades",
    target: "products",
  },
]);
const today = new Intl.DateTimeFormat("pt-BR", {
  day: "numeric",
  month: "long",
  year: "numeric",
}).format(new Date());
function customer(id) {
  return (
    data.value.users?.find((user) => Number(user.id) === Number(id))?.name ||
    `Cliente #${id}`
  );
}
</script>

<template>
  <section class="page-content overview" :aria-busy="loading">
    <header class="page-heading">
      <div>
        <span class="eyebrow">SUA LOJA EM UM OLHAR</span>
        <h1>
          Olá, {{ user.name?.split(" ")[0] }}<span class="greeting-dot">.</span>
        </h1>
        <p class="muted">Tudo o que você precisa acompanhar, em um só lugar.</p>
      </div>
      <span class="today"><Icon name="clock" :size="16" />{{ today }}</span>
    </header>
    <div v-if="error" class="notice" role="alert">
      <Icon name="alert" :size="18" /><span>{{ error }}</span
      ><button class="text-button" @click="load">Tentar novamente</button>
    </div>
    <div class="stats-grid">
      <button
        v-for="stat in stats"
        :key="stat.title"
        class="stat-card"
        @click="emit('navigate', stat.target)"
      >
        <div class="stat-top">
          <span>{{ stat.title }}</span
          ><span class="stat-icon"><Icon :name="stat.icon" :size="18" /></span>
        </div>
        <span v-if="loading" class="skeleton stat-skeleton" /><strong
          v-else
          class="stat-value"
          >{{ stat.value }}</strong
        ><span class="stat-bottom"
          >{{ stat.caption }}<Icon name="arrow" :size="15"
        /></span>
      </button>
    </div>
    <div class="welcome-strip">
      <div class="welcome-icon"><Icon name="bag" :size="30" /></div>
      <div>
        <h2>Mais organização. Mais possibilidades.</h2>
        <p>Um catálogo bem cuidado é o primeiro passo para a próxima venda.</p>
      </div>
      <button class="button secondary" @click="emit('navigate', 'products')">
        Cuidar do catálogo<Icon name="right" :size="16" />
      </button>
    </div>
    <div class="overview-grid">
      <section class="panel">
        <header class="panel-heading">
          <div>
            <h2>Últimos pedidos</h2>
            <p>As movimentações mais recentes da loja.</p>
          </div>
          <button class="text-button" @click="emit('navigate', 'orders')">
            Ver todos <Icon name="right" :size="15" />
          </button>
        </header>
        <div v-if="loading" class="compact-loading" role="status">
          <span class="spinner" /> Carregando pedidos…
        </div>
        <div v-else-if="data.orders === null" class="empty-state compact">
          <Icon name="alert" :size="28" />
          <h3>Pedidos indisponíveis</h3>
          <button class="text-button" @click="load">Tentar novamente</button>
        </div>
        <div v-else-if="!recentOrders.length" class="empty-state compact">
          <span class="empty-icon"><Icon name="orders" :size="26" /></span>
          <h3>Ainda não há pedidos</h3>
          <p>Os pedidos registrados aparecerão por aqui.</p>
          <button class="text-button" @click="emit('navigate', 'orders')">
            Ir para pedidos <Icon name="right" :size="15" />
          </button>
        </div>
        <div v-else class="table-scroll">
          <table>
            <caption class="sr-only">
              Últimos pedidos
            </caption>
            <thead>
              <tr>
                <th>Pedido / cliente</th>
                <th>Status</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="order in recentOrders" :key="order.id">
                <td>
                  <strong class="order-id"
                    >#{{ String(order.id).padStart(4, "0")
                    }}<small
                      >{{ customer(order.users_id) }} ·
                      {{ date(order.created_at) }}</small
                    ></strong
                  >
                </td>
                <td>
                  <span
                    :class="[
                      'badge',
                      order.status === 'pago'
                        ? 'success'
                        : order.status === 'cancelado'
                          ? 'neutral'
                          : 'warning',
                    ]"
                    ><i />{{
                      {
                        pago: "Pago",
                        pendente: "Pendente",
                        cancelado: "Cancelado",
                      }[order.status] || order.status
                    }}</span
                  >
                </td>
                <td class="strong-cell">{{ currency(order.total) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      <section class="panel">
        <header class="panel-heading">
          <div>
            <h2>De olho no estoque</h2>
            <p>Produtos que precisam de atenção.</p>
          </div>
          <span class="badge warning">Até 5 un.</span>
        </header>
        <div v-if="loading" class="compact-loading" role="status">
          <span class="spinner" /> Carregando produtos…
        </div>
        <div v-else-if="data.products === null" class="empty-state compact">
          <Icon name="alert" :size="28" />
          <h3>Estoque indisponível</h3>
          <button class="text-button" @click="load">Tentar novamente</button>
        </div>
        <div v-else-if="!lowStock.length" class="empty-state compact">
          <span class="empty-icon"><Icon name="check" :size="26" /></span>
          <h3>
            {{
              data.products.length
                ? "Tudo em dia por aqui"
                : "Seu catálogo está começando"
            }}
          </h3>
          <p>
            {{
              data.products.length
                ? "Nenhum produto ativo com estoque baixo."
                : "Cadastre seus produtos para acompanhar o estoque."
            }}
          </p>
        </div>
        <ul v-else class="stock-list">
          <li v-for="product in lowStock.slice(0, 5)" :key="product.id">
            <span class="product-thumb"><Icon name="box" :size="21" /></span>
            <div>
              <strong>{{ product.name }}</strong
              ><small>{{ currency(product.price) }}</small>
            </div>
            <span :class="['stock-count', { zero: !Number(product.stock) }]">{{
              Number(product.stock) ? `${product.stock} un.` : "Esgotado"
            }}</span>
          </li>
        </ul>
        <footer v-if="lowStock.length" class="panel-link">
          <button class="text-button" @click="emit('navigate', 'products')">
            Gerenciar estoque<Icon name="right" :size="15" />
          </button>
        </footer>
      </section>
    </div>
    <p class="page-footnote">
      Os números refletem os registros atuais da sua loja.
    </p>
  </section>
</template>
