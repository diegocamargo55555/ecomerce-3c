import { api, ApiError, resourceData } from "./http.js";
import { isCatalogResponse } from "../validacoes/respostas.js";

export async function getProduct(id) {
  return resourceData(await api(`/api/products/${encodeURIComponent(id)}`));
}

export async function uploadProductImage(id, file) {
  const body = new FormData();
  body.append("image", file);
  return resourceData(await api(`/api/admin/products/${encodeURIComponent(id)}/image`, {
    method: "POST", body,
  }));
}

export async function listCatalog({ search, category, sort, page }) {
  const params = new URLSearchParams({ sort, page: String(page) });
  if (search.trim()) params.set("search", search.trim());
  if (category) params.set("category_id", category);
  const response = await api(`/api/products?${params}`);
  if (!isCatalogResponse(response))
    throw new ApiError("A resposta recebida não contém um catálogo válido.");
  return response;
}
