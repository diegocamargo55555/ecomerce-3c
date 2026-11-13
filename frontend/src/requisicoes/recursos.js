import { api, ApiError, resourceData } from "./http.js";
import { isResourceList } from "../validacoes/respostas.js";

export async function listResource(key) {
  const path = key === "products" ? "admin/products" : key;
  const data = resourceData(await api(`/api/${path}`));
  if (!isResourceList(data))
    throw new ApiError("A resposta recebida não contém uma lista válida.");
  return data;
}

export async function saveResource(key, id, payload) {
  return resourceData(await api(`/api/${key}${id ? `/${id}` : ""}`, {
    method: id ? "PATCH" : "POST",
    body: payload,
  }));
}

export async function deleteResource(key, id) {
  return api(`/api/${key}/${id}`, { method: "DELETE" });
}
