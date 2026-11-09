import { afterEach, test } from "node:test";
import assert from "node:assert/strict";
import { ApiError, listCatalog, listResource } from "../src/api/http.js";

const originalFetch = globalThis.fetch;
afterEach(() => { globalThis.fetch = originalFetch; });

test("catalog sends combined filters and page to the public API and preserves metadata", async () => {
  const payload = {
    data: [{ id: 10, is_active: true, stock: 0 }],
    meta: { current_page: 2, last_page: 4, total: 29, from: 9, to: 16 },
  };
  globalThis.fetch = async (path, options) => {
    const url = new URL(path, "http://localhost");
    assert.equal(url.pathname, "/api/products");
    assert.deepEqual(Object.fromEntries(url.searchParams), {
      sort: "price-asc", page: "2", search: "Space Marines & tinta", category_id: "3",
    });
    assert.equal(options.credentials, "include");
    return Response.json(payload);
  };
  assert.deepEqual(await listCatalog({ search: " Space Marines & tinta ", category: "3", sort: "price-asc", page: 2 }), payload);
});

test("empty filters are omitted and empty catalog results are valid", async () => {
  globalThis.fetch = async (path) => {
    assert.equal(path, "/api/products?sort=newest&page=1");
    return Response.json({ data: [], meta: { total: 0, last_page: 1 } });
  };
  const response = await listCatalog({ search: "  ", category: "", sort: "newest", page: 1 });
  assert.deepEqual(response.data, []);
});

test("catalog rejects missing pagination metadata", async () => {
  globalThis.fetch = async () => Response.json({ data: [] });
  await assert.rejects(listCatalog({ search: "", category: "", sort: "newest", page: 1 }), ApiError);
});

test("management product lists use the protected endpoint without losing inactive products", async () => {
  globalThis.fetch = async (path) => {
    assert.equal(path, "/api/admin/products");
    return Response.json({ data: [{ id: 1, is_active: false }] });
  };
  assert.deepEqual(await listResource("products"), [{ id: 1, is_active: false }]);
});
