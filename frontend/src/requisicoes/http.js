export class ApiError extends Error {
  constructor(message, status = 0, errors = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
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
        : (response.status === 409 && payload?.message) ||
          messages[response.status] ||
          "Não foi possível concluir esta ação.",
      response.status,
      payload?.errors || {},
    );
  }
  return payload;
}

export function resourceData(response) {
  return response?.data ?? response;
}

