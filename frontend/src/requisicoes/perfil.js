import { api, resourceData } from "./http.js";

export async function getAddresses() {
  return resourceData(await api("/api/my/addresses"));
}

export async function createAddress(address) {
  return resourceData(await api("/api/my/addresses", { method: "POST", body: address }));
}

export async function updateProfile(data) {
  return resourceData(await api("/api/me", { method: "PATCH", body: data }));
}

export async function updateAddress(id, data) {
  return resourceData(await api(`/api/my/addresses/${encodeURIComponent(id)}`, { method: "PATCH", body: data }));
}

export async function deleteAddress(id) {
  return api(`/api/my/addresses/${encodeURIComponent(id)}`, { method: "DELETE" });
}
