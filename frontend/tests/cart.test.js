import { afterEach, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { getCart, updateCartItem, removeCartItem } from "../src/api/http.js";
import { login } from "../src/api/auth/login.js";
import { register } from "../src/api/auth/register.js";

const originalFetch = globalThis.fetch;
beforeEach(() => { globalThis.document = { cookie: "XSRF-TOKEN=token" }; });
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.document; });

test("cart reads and mutations use the current session and preserve server totals", async () => {
  const cart = { id: 1, items: [{ id: 7, quantity: 3, subtotal: "0.30" }], total: "0.30", item_count: 3 };
  const calls = [];
  globalThis.fetch = async (path, options) => {
    calls.push([path, options.method, options.body && JSON.parse(options.body)]);
    assert.equal(options.credentials, "include");
    return Response.json({ data: cart });
  };
  assert.deepEqual(await getCart(), cart);
  assert.deepEqual(await updateCartItem(7, 3), cart);
  assert.deepEqual(await removeCartItem(7), cart);
  assert.deepEqual(calls, [
    ["/api/cart", "GET", undefined],
    ["/api/cart/items/7", "PATCH", { quantity: 3 }],
    ["/api/cart/items/7", "DELETE", undefined],
  ]);
});

test("login and registration preserve the merge warnings alongside the user", async () => {
  const response = { data: { id: 1, role: "cliente" }, cart_warnings: ["Quantidade ajustada por limite de estoque."] };
  globalThis.fetch = async () => Response.json(response);
  assert.deepEqual(await login({}), response);
  assert.deepEqual(await register({}), response);
});

test("invalid cart quantities expose field errors instead of replacing server state", async () => {
  globalThis.fetch = async () => Response.json({ errors: { quantity: ["Estoque insuficiente."] } }, { status: 422 });
  await assert.rejects(updateCartItem(7, 10), (error) => error.status === 422 && error.errors.quantity[0] === "Estoque insuficiente.");
});
