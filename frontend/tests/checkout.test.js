import { afterEach, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { createAddress, createOrder, getAddresses, getCheckout, getOrder, payOrder } from "../src/api/http.js";

const originalFetch = globalThis.fetch;
beforeEach(() => { globalThis.document = { cookie: "XSRF-TOKEN=token" }; });
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.document; });

test("checkout and address calls preserve server totals and use only the current account", async () => {
  const calls = [];
  const quote = { subtotal: "0.30", shipping_cost: "20.00", total: "20.30", items: [] };
  globalThis.fetch = async (path, options) => {
    assert.equal(options.credentials, "include");
    calls.push([path, options.method, options.body && JSON.parse(options.body)]);
    return Response.json({ data: path === "/api/checkout" ? quote : [] });
  };
  assert.deepEqual(await getCheckout(), quote);
  await getAddresses();
  await createAddress({ street: "Rua A" });
  await createOrder(7);
  assert.deepEqual(calls, [
    ["/api/checkout", "GET", undefined],
    ["/api/my/addresses", "GET", undefined],
    ["/api/my/addresses", "POST", { street: "Rua A" }],
    ["/api/orders", "POST", { address_id: 7 }],
  ]);
});

test("simulated payment sends no financial or card data and loads the updated order", async () => {
  const calls = [];
  const order = { id: 9, status: "pago", total: "20.30" };
  globalThis.fetch = async (path, options) => {
    calls.push([path, options.method, options.body]);
    return Response.json({ data: order });
  };
  assert.deepEqual(await payOrder(9), order);
  assert.deepEqual(await getOrder(9), order);
  assert.deepEqual(calls, [["/api/orders/9/pay", "POST", undefined], ["/api/orders/9", "GET", undefined]]);
});

test("a repeated payment has a readable conflict and stock failures retain field messages", async () => {
  globalThis.fetch = async () => Response.json({}, { status: 409 });
  await assert.rejects(payOrder(9), error => error.status === 409 && error.message.includes("estado atual"));
  globalThis.fetch = async () => Response.json({ errors: { cart: ["Marine: estoque insuficiente."] } }, { status: 422 });
  await assert.rejects(createOrder(7), error => error.errors.cart[0] === "Marine: estoque insuficiente.");
});

