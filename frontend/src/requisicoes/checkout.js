import { api, resourceData } from "./http.js";

export async function getCheckout() {
  return resourceData(await api("/api/checkout"));
}

export async function createOrder(addressId) {
  return resourceData(await api("/api/orders", { method: "POST", body: { address_id: addressId } }));
}
