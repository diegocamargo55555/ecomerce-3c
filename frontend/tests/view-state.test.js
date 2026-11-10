import { afterEach, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { createSSRApp } from "vue";
import { renderToString } from "vue/server-renderer";
import { useAuth } from "../src/manipulacao/useAuth.js";
import { useProduct } from "../src/manipulacao/useProduct.js";
import { useOrder } from "../src/manipulacao/useOrder.js";
import { useMyOrders } from "../src/manipulacao/useMyOrders.js";

const originalFetch = globalThis.fetch;
beforeEach(() => {
  globalThis.document = { cookie: "XSRF-TOKEN=token", title: "" };
});
afterEach(() => {
  globalThis.fetch = originalFetch;
  delete globalThis.document;
});

async function stateFor(create) {
  let state;
  await renderToString(createSSRApp({
    setup() {
      state = create();
      return () => null;
    },
  }));
  return state;
}

test("auth compositions keep independent forms and emit the authenticated user and cart warnings", async () => {
  const events = [];
  const first = await stateFor(() => useAuth((...event) => events.push(event)));
  const second = await stateFor(() => useAuth(() => {}));
  const user = { id: 2, name: "Cliente", role: "cliente" };
  Object.assign(first.form, { email: " cliente@example.com ", password: " senha com espaços " });
  assert.equal(second.form.email, "");
  globalThis.fetch = async (path, options) => {
    assert.equal(path, "/api/login");
    assert.equal(first.busy.value, true);
    assert.deepEqual(JSON.parse(options.body), {
      email: "cliente@example.com", password: " senha com espaços ",
    });
    return Response.json({ data: user, cart_warnings: ["Estoque ajustado."] });
  };

  await first.submit();

  assert.deepEqual(events, [["authenticated", user, ["Estoque ajustado."]]]);
  assert.equal(first.form.password, "");
  assert.equal(first.busy.value, false);
});

test("auth failures retain field errors without authenticating or keeping the form busy", async () => {
  const events = [];
  const state = await stateFor(() => useAuth((...event) => events.push(event)));
  const errors = { email: ["Confira o e-mail informado."] };
  globalThis.fetch = async () => Response.json({ errors }, { status: 422 });

  await state.submit();

  assert.deepEqual(state.errors.value, errors);
  assert.match(state.error.value, /campos/);
  assert.equal(state.busy.value, false);
  assert.deepEqual(events, []);
});

test("product actions validate quantities before requesting and update their own feedback", async () => {
  const events = [];
  const writes = [];
  globalThis.fetch = async (path, options) => {
    if (options.method === "GET")
      return Response.json({ data: { id: 7, name: "Produto", stock: 3 } });
    writes.push({ path, body: JSON.parse(options.body) });
    return Response.json({ data: { quantity: 2 } });
  };
  const state = await stateFor(() => useProduct({ productId: "7" }, (...event) => events.push(event)));
  await state.load();
  for (const quantity of [0, 4, 1.5, "2"]) {
    state.quantity.value = quantity;
    await state.add();
  }
  assert.deepEqual(writes, []);

  state.quantity.value = 2;
  await state.add();

  assert.deepEqual(writes, [{ path: "/api/cart/items", body: { product_id: 7, quantity: 2 } }]);
  assert.deepEqual(events, [["cart-updated"]]);
  assert.match(state.added.value, /2 unidade/);
  assert.equal(state.saving.value, false);
});

test("order detail and history share status labels while admin actions preserve the next transition", async () => {
  let order = { id: 9, users_id: 2, status: "pago", payments: [] };
  const writes = [];
  globalThis.fetch = async (path, options) => {
    if (options.method === "PATCH") {
      const body = JSON.parse(options.body);
      writes.push({ path, body });
      order = { ...order, status: body.status };
    }
    return Response.json({ data: path === "/api/my/orders" ? [order] : order });
  };
  const detail = await stateFor(() => useOrder({ orderId: "9", user: { id: 1, role: "admin" } }));
  const history = await stateFor(() => useMyOrders());
  await detail.load();
  assert.equal(detail.statusNames.pago, "Pago");
  assert.equal(detail.nextStatus.value, "enviado");

  await detail.advanceStatus();
  await history.load();

  assert.deepEqual(writes, [{ path: "/api/admin/orders/9/status", body: { status: "enviado" } }]);
  assert.equal(detail.nextStatus.value, "entregue");
  assert.equal(detail.notice.value, "Status atualizado: Enviado.");
  assert.equal(history.statusNames[history.orders.value[0].status], "Enviado");
  assert.equal(detail.busy.value, false);
});
