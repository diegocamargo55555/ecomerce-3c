import { fileURLToPath, URL } from "node:url";

import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  const target = env.API_PROXY_TARGET || "http://127.0.0.1:8000";
  const sessionOrigin = env.API_SESSION_ORIGIN || new URL(target).origin;
  // Keep cookies and requests on the frontend origin. Sanctum sees the existing
  // backend origin, so no CORS or stateful-domain changes are needed in Laravel.
  const proxy = Object.fromEntries(
    ["/api", "/sanctum"].map((path) => [
      path,
      {
        target,
        changeOrigin: true,
        headers: { origin: sessionOrigin, referer: `${sessionOrigin}/` },
      },
    ]),
  );
  return {
    plugins: [vue()],
    server: { host: "127.0.0.1", port: 5173, strictPort: true, proxy },
    preview: { host: "127.0.0.1", port: 5174, strictPort: true, proxy },
    resolve: {
      alias: { "@": fileURLToPath(new URL("./src", import.meta.url)) },
    },
  };
});
