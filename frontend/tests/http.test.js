import { afterEach, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { api, ApiError, listResource, resourceData } from "../src/api/http.js";

const originalFetch = globalThis.fetch;
beforeEach(() => {
  globalThis.document = { cookie: "XSRF-TOKEN=existing%3Dtoken" };
  globalThis.window = new EventTarget();
});
afterEach(() => {
  globalThis.fetch = originalFetch;
  delete globalThis.document;
  delete globalThis.window;
});

test("writes send JSON, session cookies and the decoded XSRF token", async () => {
  globalThis.fetch = async (path, options) => {
    assert.equal(path, "/api/products");
    assert.equal(options.method, "POST");
    assert.equal(options.credentials, "include");
    assert.equal(options.headers["X-XSRF-TOKEN"], "existing=token");
    assert.equal(options.headers["Content-Type"], "application/json");
    assert.deepEqual(JSON.parse(options.body), {
      name: "Caderno",
      price: 10.5,
    });
    return Response.json({ id: 7 }, { status: 201 });
  };
  assert.deepEqual(
    await api("/api/products", {
      method: "POST",
      body: { name: "Caderno", price: 10.5 },
    }),
    { id: 7 },
  );
});

test("first write gets the CSRF cookie before sending the payload", async () => {
  document.cookie = "";
  const paths = [];
  globalThis.fetch = async (path) => {
    paths.push(path);
    if (path === "/sanctum/csrf-cookie") {
      document.cookie = "XSRF-TOKEN=new";
      return new Response(null, { status: 204 });
    }
    return Response.json({ data: { id: 1 } });
  };
  await api("/api/login", {
    method: "POST",
    body: { email: "user@example.test", password: "password" },
  });
  assert.deepEqual(paths, ["/sanctum/csrf-cookie", "/api/login"]);
});

test("419 refreshes CSRF once and retries with the fresh token", async () => {
  const paths = [];
  let attempts = 0;
  globalThis.fetch = async (path, options) => {
    paths.push(path);
    if (path === "/sanctum/csrf-cookie") {
      document.cookie = "XSRF-TOKEN=renewed";
      return new Response(null, { status: 204 });
    }
    if (++attempts === 1) return Response.json({}, { status: 419 });
    assert.equal(options.headers["X-XSRF-TOKEN"], "renewed");
    return new Response(null, { status: 204 });
  };
  assert.equal(await api("/api/logout", { method: "POST" }), null);
  assert.deepEqual(paths, [
    "/api/logout",
    "/sanctum/csrf-cookie",
    "/api/logout",
  ]);
});

test("a persistent 419 stops retrying and reports session expiry", async () => {
  let attempts = 0;
  globalThis.fetch = async (path) => {
    attempts++;
    return path === "/sanctum/csrf-cookie"
      ? new Response(null, { status: 204 })
      : Response.json({}, { status: 419 });
  };
  await assert.rejects(
    api("/api/products", { method: "POST" }),
    (error) => error.status === 419,
  );
  assert.equal(attempts, 3);
});

test("validation errors preserve the backend field messages", async () => {
  const errors = { price: ["O preço precisa ser maior ou igual a zero."] };
  globalThis.fetch = async () => Response.json({ errors }, { status: 422 });
  await assert.rejects(
    api("/api/products", { method: "POST", body: { price: -1 } }),
    (error) => {
      assert.ok(error instanceof ApiError);
      assert.equal(error.status, 422);
      assert.deepEqual(error.errors, errors);
      return true;
    },
  );
});

test("protected 401 notifies the app, initial /me 401 does not", async () => {
  let events = 0;
  window.addEventListener("session-expired", () => events++);
  globalThis.fetch = async () => Response.json({}, { status: 401 });
  await assert.rejects(api("/api/me"));
  assert.equal(events, 0);
  await assert.rejects(api("/api/products"));
  assert.equal(events, 1);
});

test("204 deletes do not try to parse an empty body", async () => {
  globalThis.fetch = async () => new Response(null, { status: 204 });
  assert.equal(await api("/api/products/7", { method: "DELETE" }), null);
});

test("server errors are readable and do not expose backend traces", async () => {
  globalThis.fetch = async () =>
    Response.json(
      { message: "SQLSTATE database secret", trace: [] },
      { status: 500 },
    );
  await assert.rejects(
    api("/api/products"),
    (error) => error.status === 500 && !error.message.includes("SQLSTATE"),
  );
});

test("network failures offer a retry message", async () => {
  globalThis.fetch = async () => {
    throw new TypeError("Failed to fetch");
  };
  await assert.rejects(
    api("/api/products"),
    (error) => error.status === 0 && error.message.includes("tente novamente"),
  );
});

test("resource collections unwrap data and reject unexpected payloads", async () => {
  globalThis.fetch = async () => Response.json({ data: [{ id: 7 }] });
  assert.deepEqual(await listResource("products"), [{ id: 7 }]);
  globalThis.fetch = async () => Response.json({ message: "Unexpected" });
  await assert.rejects(listResource("products"), ApiError);
  assert.deepEqual(resourceData({ id: 1 }), { id: 1 });
  assert.deepEqual(resourceData({ data: { id: 2 } }), { id: 2 });
});
