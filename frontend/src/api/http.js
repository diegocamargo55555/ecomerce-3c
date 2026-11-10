export class ApiError extends Error {
  constructor(message, status = 0, errors = {}, serverMessage = "") {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
    // Motivo informado pela API (ex.: por que uma exclusão foi recusada).
    this.serverMessage = serverMessage;
  }
}

let csrfRequest;

function csrfToken() {
  const cookie = document.cookie
    .split("; ")
    .find((value) => value.startsWith("XSRF-TOKEN="));
  return cookie ? decodeURIComponent(cookie.slice("XSRF-TOKEN=".length)) : "";
}

async function request(path, options = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 15000);
  try {
    return await fetch(path, {
      ...options,
      credentials: "include",
      signal: controller.signal,
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        ...options.headers,
      },
    });
  } catch (error) {
    throw new ApiError(
      error.name === "AbortError"
        ? "A solicitação demorou mais que o esperado. Tente novamente."
        : "Não foi possível conectar. Verifique sua conexão e tente novamente.",
    );
  } finally {
    clearTimeout(timeout);
  }
}

async function ensureCsrf(refresh = false) {
  if (!refresh && csrfToken()) return;
  if (!csrfRequest) {
    csrfRequest = request("/sanctum/csrf-cookie")
      .then(async (response) => {
        if (!response.ok)
          throw new ApiError(
            "Não foi possível iniciar uma sessão. Tente novamente.",
            response.status,
          );
      })
      .finally(() => {
        csrfRequest = undefined;
      });
  }
  await csrfRequest;
}

export async function api(path, { method = "GET", body } = {}, retry = true) {
  const writes = !["GET", "HEAD"].includes(method);
  if (writes) await ensureCsrf();
  // FormData (upload de arquivo): o navegador define o Content-Type multipart.
  const multipart = typeof FormData !== "undefined" && body instanceof FormData;
  const response = await request(path, {
    method,
    headers: writes
      ? {
          ...(multipart ? {} : { "Content-Type": "application/json" }),
          "X-XSRF-TOKEN": csrfToken(),
        }
      : {},
    ...(body === undefined
      ? {}
      : { body: multipart ? body : JSON.stringify(body) }),
  });

  if (response.status === 419 && retry) {
    await ensureCsrf(true);
    return api(path, { method, body }, false);
  }
  const payload =
    response.status === 204 ? null : await response.json().catch(() => null);
  if (!response.ok) {
    if (
      response.status === 401 &&
      !["/api/login", "/api/register"].includes(path) &&
      !(path === "/api/me" && method === "GET")
    ) {
      window.dispatchEvent(new Event("session-expired"));
    }
    const messages = {
      401: "Sua sessão terminou. Entre novamente para continuar.",
      403: "Você não tem permissão para realizar esta ação.",
      404: "Este registro não está mais disponível. Atualize a lista.",
      409: "Esta operação não está disponível no estado atual do pedido.",
      413: "O arquivo enviado é grande demais.",
      419: "Sua sessão expirou. Atualize a página e tente novamente.",
      422: "Confira os campos indicados e tente novamente.",
      429: "Muitas tentativas. Aguarde um minuto e tente novamente.",
    };
    throw new ApiError(
      response.status >= 500
        ? "Não foi possível concluir a solicitação. Tente novamente em instantes."
        : messages[response.status] || "Não foi possível concluir esta ação.",
      response.status,
      payload?.errors || {},
      response.status < 500 ? payload?.message || "" : "",
    );
  }
  return payload;
}

export function resourceData(response) {
  return response?.data ?? response;
}

export async function getProduct(id) {
  return resourceData(await api(`/api/products/${encodeURIComponent(id)}`));
}

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

export async function getCheckout() {
  return resourceData(await api("/api/checkout"));
}

export async function getAddresses() {
  return resourceData(await api("/api/my/addresses"));
}

export async function createAddress(address) {
  return resourceData(await api("/api/my/addresses", { method: "POST", body: address }));
}

export async function createOrder(addressId) {
  return resourceData(await api("/api/orders", { method: "POST", body: { address_id: addressId } }));
}

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

export async function uploadProductImage(id, file) {
  const body = new FormData();
  body.append("image", file);
  return resourceData(await api(`/api/admin/products/${encodeURIComponent(id)}/image`, {
    method: "POST", body,
  }));
}

export async function listResource(key) {
  const path = key === "products" ? "admin/products" : key;
  const data = resourceData(await api(`/api/${path}`));
  if (!Array.isArray(data))
    throw new ApiError("A resposta recebida não contém uma lista válida.");
  return data;
}

export async function listCatalog({ search, category, sort, page }) {
  const params = new URLSearchParams({ sort, page: String(page) });
  if (search.trim()) params.set("search", search.trim());
  if (category) params.set("category_id", category);
  const response = await api(`/api/products?${params}`);
  if (
    !Array.isArray(response?.data) ||
    !Number.isInteger(response?.meta?.total) ||
    !Number.isInteger(response?.meta?.last_page)
  )
    throw new ApiError("A resposta recebida não contém um catálogo válido.");
  return response;
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
