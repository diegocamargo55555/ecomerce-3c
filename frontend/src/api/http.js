export class ApiError extends Error {
  constructor(message, status = 0, errors = {}) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

let csrfRequest;
const token = () =>
  document.cookie
    .split("; ")
    .find((cookie) => cookie.startsWith("XSRF-TOKEN="))
    ?.split("=")
    .slice(1)
    .join("=");

async function csrf() {
  if (!csrfRequest) {
    csrfRequest = fetch("/sanctum/csrf-cookie", {
      credentials: "include",
      headers: { Accept: "application/json" },
    })
      .then((response) => {
        if (!response.ok)
          throw new ApiError(
            "Não foi possível iniciar sua sessão.",
            response.status,
          );
      })
      .finally(() => {
        csrfRequest = null;
      });
  }
  return csrfRequest;
}

export async function api(path, { body, ...options } = {}, retried = false) {
  const method = (options.method || "GET").toUpperCase();
  const mutating = !["GET", "HEAD"].includes(method);
  try {
    if (mutating && !token()) await csrf();
    const headers = {
      Accept: "application/json",
      "X-Requested-With": "XMLHttpRequest",
      ...options.headers,
    };
    if (body !== undefined) headers["Content-Type"] = "application/json";
    if (mutating && token())
      headers["X-XSRF-TOKEN"] = decodeURIComponent(token());
    const response = await fetch(path, {
      ...options,
      method,
      headers,
      credentials: "include",
      body: body === undefined ? undefined : JSON.stringify(body),
    });
    if (response.status === 419 && !retried) {
      await csrf();
      return api(path, { body, ...options }, true);
    }
    const data =
      response.status === 204 ? null : await response.json().catch(() => null);
    if (!response.ok) {
      if (response.status === 401)
        window.dispatchEvent(new Event("auth:expired"));
      const messages = {
        401: "Entre na sua conta para continuar.",
        403: "Você não tem permissão para acessar esta área.",
        404: "O conteúdo solicitado não foi encontrado.",
        419: "Sua sessão expirou. Atualize a página e tente novamente.",
        422: "Confira os dados informados.",
        429: "Muitas tentativas. Aguarde um minuto e tente novamente.",
      };
      const firstError = Object.values(data?.errors || {}).flat()[0];
      throw new ApiError(
        firstError ||
          messages[response.status] ||
          "Não foi possível concluir a solicitação. Tente novamente.",
        response.status,
        data?.errors,
      );
    }
    return data;
  } catch (error) {
    if (error instanceof ApiError) throw error;
    throw new ApiError(
      "Não foi possível conectar à loja. Verifique sua conexão e tente novamente.",
    );
  }
}

export const resourceData = (response) => response?.data ?? response;
export const apiData = async (path, options) =>
  resourceData(await api(path, options));
