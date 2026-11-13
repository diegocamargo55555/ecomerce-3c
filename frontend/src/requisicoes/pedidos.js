import { api, resourceData } from "./http.js";

export async function getOrder(id) {
  return resourceData(await api(`/api/orders/${encodeURIComponent(id)}`));
}

export async function payOrder(id) {
  return resourceData(await api(`/api/orders/${encodeURIComponent(id)}/pay`, { method: "POST" }));
}

export async function getMyOrders() {
  return resourceData(await api('/api/my/orders'));
}

export async function cancelOrder(id) {
  return resourceData(await api(`/api/orders/${encodeURIComponent(id)}/cancel`, { method: 'POST' }));
}

export async function updateOrderStatus(id, status) {
  return resourceData(await api(`/api/admin/orders/${encodeURIComponent(id)}/status`, {
    method: "PATCH", body: { status },
  }));
}
