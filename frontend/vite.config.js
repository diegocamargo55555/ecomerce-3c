import { fileURLToPath, URL } from "node:url";
import { defineConfig, loadEnv } from "vite";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), "");
  const target = env.API_PROXY_TARGET || "http://127.0.0.1:8000";
  const proxy = {
    "/api": { target, changeOrigin: false },
    "/sanctum": { target, changeOrigin: false },
  };
  return {
    plugins: [vue(), vueDevTools()],
    resolve: {
      alias: { "@": fileURLToPath(new URL("./src", import.meta.url)) },
    },
    server: { port: 5173, strictPort: true, host: "0.0.0.0", proxy },
    preview: { proxy },
  };
});
