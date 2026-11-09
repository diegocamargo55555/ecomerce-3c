import { afterEach, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { login } from "../src/api/auth/login.js";
import { register } from "../src/api/auth/register.js";
import { getCurrentUser } from "../src/api/auth/me.js";
import { logout } from "../src/api/auth/logout.js";

const originalFetch = globalThis.fetch;
beforeEach(() => {
  globalThis.document = { cookie: "XSRF-TOKEN=token" };
});
afterEach(() => {
  globalThis.fetch = originalFetch;
  delete globalThis.document;
});

test("authentication stays on relative URLs and preserves the returned role", async () => {
  const calls = [];
  const user = { id: 1, name: "Cliente", role: "cliente" };
  globalThis.fetch = async (path, options) => {
    calls.push([path, options.method]);
    assert.equal(options.credentials, "include");
    if (options.method === "POST") {
      assert.equal(options.headers["X-XSRF-TOKEN"], "token");
    }
    return path === "/api/logout"
      ? new Response(null, { status: 204 })
      : Response.json({ data: user });
  };
  assert.equal((await register({})).data.role, "cliente");
  user.role = "admin";
  assert.equal((await login({})).data.role, "admin");
  assert.deepEqual(await getCurrentUser(), user);
  assert.equal(await logout(), null);
  assert.deepEqual(calls, [
    ["/api/register", "POST"],
    ["/api/login", "POST"],
    ["/api/me", "GET"],
    ["/api/logout", "POST"],
  ]);
});
