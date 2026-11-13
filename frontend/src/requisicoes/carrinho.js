import { api, resourceData } from "./http.js";

export async function addToCart(productId, quantity) {
  return resourceData(await api("/api/cart/items", {
    method: "POST", body: { product_id: productId, quantity },
  }));
}

export async function getCart() {
  return resourceData(await api("/api/cart"));
}

export async function updateCartItem(id, quantity) {
  return resourceData(await api(`/api/cart/items/${encodeURIComponent(id)}`, {
    method: "PATCH", body: { quantity },
  }));
}

export async function removeCartItem(id) {
  return resourceData(await api(`/api/cart/items/${encodeURIComponent(id)}`, { method: "DELETE" }));
}
