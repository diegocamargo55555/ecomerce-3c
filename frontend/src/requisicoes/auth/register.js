import { api } from "../http.js";

export function register(credentials) {
  return api("/api/register", { method: "POST", body: credentials });
}
