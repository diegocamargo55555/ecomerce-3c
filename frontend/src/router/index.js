import { createRouter, createWebHistory } from "vue-router";
import { restoreSession, session } from "@/stores/shop";

const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to, from, saved) {
    return saved || (to.path === from.path ? undefined : { top: 0 });
  },
  routes: [
    {
      path: "/",
      component: () => import("@/views/CatalogView.vue"),
      meta: { title: "Encontre seu próximo favorito" },
    },
    {
      path: "/produtos/:id(\\d+)",
      component: () => import("@/views/ProductView.vue"),
      meta: { title: "Produto" },
    },
    {
      path: "/login",
      component: () => import("@/views/AuthView.vue"),
      props: { mode: "login" },
      meta: { title: "Entrar", guestOnly: true },
    },
    {
      path: "/cadastro",
      component: () => import("@/views/AuthView.vue"),
      props: { mode: "register" },
      meta: { title: "Criar conta", guestOnly: true },
    },
    {
      path: "/carrinho",
      component: () => import("@/views/CartView.vue"),
      meta: { title: "Meu carrinho", requiresAuth: true },
    },
    {
      path: "/checkout",
      component: () => import("@/views/CheckoutView.vue"),
      meta: { title: "Finalizar pedido", requiresAuth: true },
    },
    {
      path: "/pedidos",
      component: () => import("@/views/OrdersView.vue"),
      meta: { title: "Meus pedidos", requiresAuth: true },
    },
    {
      path: "/admin",
      component: () => import("@/layouts/AdminLayout.vue"),
      meta: { requiresAuth: true, admin: true },
      children: [
        { path: "", redirect: "/admin/produtos" },
        {
          path: "produtos",
          component: () => import("@/views/admin/ProductsView.vue"),
          meta: { title: "Gerenciar produtos" },
        },
        {
          path: "categorias",
          component: () => import("@/views/admin/CategoriesView.vue"),
          meta: { title: "Gerenciar categorias" },
        },
        {
          path: "pedidos",
          component: () => import("@/views/admin/OrdersView.vue"),
          meta: { title: "Gerenciar pedidos" },
        },
      ],
    },
    {
      path: "/acesso-negado",
      component: () => import("@/views/MessageView.vue"),
      props: { forbidden: true },
      meta: { title: "Acesso restrito" },
    },
    {
      path: "/:pathMatch(.*)*",
      component: () => import("@/views/MessageView.vue"),
      meta: { title: "Página não encontrada" },
    },
  ],
});
router.beforeEach(async (to) => {
  await restoreSession();
  if (to.meta.requiresAuth && !session.user)
    return { path: "/login", query: { redirect: to.fullPath } };
  if (to.meta.admin && !session.user?.is_admin) return "/acesso-negado";
  if (to.meta.guestOnly && session.user) return "/";
});
router.afterEach((to) => {
  document.title = `${to.meta.title || "Loja"} | Loja 3C`;
});
window.addEventListener("auth:expired", () => {
  if (router.currentRoute.value.meta.requiresAuth)
    router.replace({
      path: "/login",
      query: { redirect: router.currentRoute.value.fullPath },
    });
});
export default router;
