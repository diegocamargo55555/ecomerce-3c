import { after, afterEach, before, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { createServer } from "vite";
import vue from "@vitejs/plugin-vue";
import { createSSRApp } from "vue";
import { renderToString } from "vue/server-renderer";

const originalFetch = globalThis.fetch;
let server, component, record, writes, events, failUpload;

before(async () => {
  server = await createServer({
    configFile: false,
    plugins: [vue()],
    server: { middlewareMode: true, watch: null },
    optimizeDeps: { noDiscovery: true, include: [] },
  });
  ({ default: component } = await server.ssrLoadModule("/src/components/ResourceView.vue"));
});
after(async () => { await server?.close(); });
beforeEach(() => {
  record = {
    id: 7, name: "Produto", category_id: 1, description: "Descrição",
    price: "10.00", stock: 10, image_url: null, is_active: true,
  };
  writes = [];
  events = [];
  failUpload = false;
  globalThis.document = { cookie: "XSRF-TOKEN=token" };
  globalThis.fetch = async (path, options) => {
    if (path.endsWith("/image")) {
      if (failUpload) return Response.json({}, { status: 500 });
      record.image_url = "http://localhost/storage/products/new.png";
      return Response.json({ data: { ...record } });
    }
    if (["POST", "PATCH"].includes(options.method)) {
      const body = JSON.parse(options.body);
      writes.push({ path, method: options.method, body });
      Object.assign(record, body);
      return Response.json({ data: { ...record } });
    }
    return Response.json({ data: [] });
  };
});
afterEach(() => {
  globalThis.fetch = originalFetch;
  delete globalThis.document;
});

async function editor(resourceKey = "products", user = { id: 1 }) {
  let state;
  await renderToString(createSSRApp({
    setup() {
      state = component.setup({ resourceKey, user }, {
        expose() {}, emit: (...event) => events.push(event),
      });
      return () => null;
    },
  }));
  await state.load();
  return state;
}

test("editing a product name preserves stock reduced by a sale after opening the form", async () => {
  const state = await editor();
  state.openForm({ ...record });
  record.stock = 9;
  state.form.name = "Novo nome";

  await state.save();

  assert.deepEqual(writes, [{ path: "/api/products/7", method: "PATCH", body: { name: "Novo nome" } }]);
  assert.equal(record.stock, 9);
});

test("edited stock, prices, checkboxes and cleared optional fields are still submitted", async () => {
  record.image_url = "https://example.com/old.png";
  const state = await editor();
  state.openForm({ ...record });
  Object.assign(state.form, { stock: 0, price: "12.50", is_active: false, image_url: "" });

  await state.save();

  assert.deepEqual(writes[0].body, { price: 12.5, stock: 0, image_url: null, is_active: false });
});

test("creating a product still submits all fields and defaults", async () => {
  const state = await editor();
  state.openForm();
  Object.assign(state.form, { name: "Novo", category_id: 1, description: "Descrição", price: "12.50" });

  await state.save();

  assert.deepEqual(writes, [{ path: "/api/products", method: "POST", body: {
    name: "Novo", category_id: 1, description: "Descrição", price: 12.5,
    stock: 0, image_url: null, is_active: true,
  } }]);
});

test("retrying a failed image upload does not resend stock saved before a later sale", async () => {
  const state = await editor();
  state.openForm({ ...record });
  state.form.stock = 12;
  state.form.image = new File(["png"], "foto.png", { type: "image/png" });
  failUpload = true;
  await state.save();
  assert.match(state.formError.value, /produto foi salvo/);

  record.stock = 11;
  failUpload = false;
  await state.save();

  assert.deepEqual(writes.map(write => write.body), [{ stock: 12 }, {}]);
  assert.equal(record.stock, 11);
  assert.equal(record.image_url, "http://localhost/storage/products/new.png");
});

test("a partial self-edit preserves the signed-in user's other fields", async () => {
  record = { id: 1, name: "Admin", email: "admin@example.com", role: "admin" };
  const state = await editor("users", { ...record });
  state.openForm({ ...record });
  state.form.name = "Novo nome";

  await state.save();

  assert.deepEqual(writes[0].body, { name: "Novo nome" });
  assert.deepEqual(events.find(([event]) => event === "user-updated"), ["user-updated", record]);
});
