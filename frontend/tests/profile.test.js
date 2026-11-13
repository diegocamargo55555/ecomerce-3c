import { afterEach, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { updateProfile, updateAddress, deleteAddress } from "../src/requisicoes/perfil.js";

const originalFetch = globalThis.fetch;
beforeEach(() => { globalThis.document = { cookie: "XSRF-TOKEN=token" }; });
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.document; delete globalThis.window; });

test("profile and own addresses keep session, CSRF and updated address identifiers", async () => {
  const calls = [];
  globalThis.fetch = async (path, options) => {
    assert.equal(options.credentials, "include");
    assert.equal(options.headers["X-XSRF-TOKEN"], "token");
    calls.push([path, options.method, options.body && JSON.parse(options.body)]);
    return options.method === "DELETE" ? new Response(null, { status: 204 }) : Response.json({ data: { id: 8 } });
  };
  assert.deepEqual(await updateProfile({ name: "Novo nome" }), { id: 8 });
  assert.deepEqual(await updateAddress(2, { street: "Rua B" }), { id: 8 });
  assert.equal(await deleteAddress(8), null);
  assert.deepEqual(calls, [["/api/me", "PATCH", { name: "Novo nome" }], ["/api/my/addresses/2", "PATCH", { street: "Rua B" }], ["/api/my/addresses/8", "DELETE", undefined]]);
});

test("profile validation retains field messages and expired writes notify the app", async () => {
  globalThis.fetch = async () => Response.json({ errors: { email: ["E-mail já cadastrado."] } }, { status: 422 });
  await assert.rejects(updateProfile({ email: "existing@example.com" }), error => error.errors.email[0] === "E-mail já cadastrado.");
  let expired = false;
  globalThis.window = { dispatchEvent: event => { expired = event.type === "session-expired"; } };
  globalThis.fetch = async () => Response.json({}, { status: 401 });
  await assert.rejects(updateProfile({ name: "Novo" }), error => error.status === 401);
  assert.equal(expired, true);
});
