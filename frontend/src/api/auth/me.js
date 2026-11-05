import { apiData } from "../http.js";

export function getCurrentUser() {
  return apiData("/api/me");
}
