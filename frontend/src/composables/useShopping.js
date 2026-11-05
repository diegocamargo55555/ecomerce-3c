import { useRouter } from "vue-router";
import { session, addToCart, notify } from "@/stores/shop";

export function useShopping() {
  const router = useRouter();
  return async (product, quantity = 1) => {
    if (!session.user) {
      sessionStorage.setItem(
        "pendingCartItem",
        JSON.stringify({ productId: product.id, quantity }),
      );
      return router.push({ path: "/login", query: { redirect: "/carrinho" } });
    }
    try {
      if (await addToCart(product.id, quantity))
        notify("Produto adicionado ao carrinho.");
    } catch (error) {
      notify(error.message, "error");
    }
  };
}
