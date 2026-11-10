import { api, resourceData } from "./http.js";

export async function getOverview() {
  return resourceData(await api("/api/admin/overview"));
}
