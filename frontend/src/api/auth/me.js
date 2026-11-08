import { api, resourceData } from "../http.js";

export async function getCurrentUser() {
  return resourceData(await api("/api/me"));
}
