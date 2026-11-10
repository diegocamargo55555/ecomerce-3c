import { afterEach, beforeEach, test } from "node:test";
import assert from "node:assert/strict";
import { uploadProductImage, updateCartItem } from "../src/api/http.js";

const originalFetch = globalThis.fetch;
beforeEach(() => { globalThis.document = { cookie: "XSRF-TOKEN=token" }; });
afterEach(() => { globalThis.fetch = originalFetch; delete globalThis.document; });

test("product image upload sends multipart form data with CSRF and returns the product", async () => {
  const file = new File(["png"], "foto.png", { type: "image/png" });
  globalThis.fetch = async (path, options) => {
    assert.equal(path, "/api/admin/products/7/image");
    assert.equal(options.method, "POST");
    assert.equal(options.credentials, "include");
    assert.equal(options.headers["X-XSRF-TOKEN"], "token");
    // The browser must set the multipart boundary itself.
    assert.equal(options.headers["Content-Type"], undefined);
    assert.ok(options.body instanceof FormData);
    assert.equal(options.body.get("image").name, "foto.png");
    return Response.json({ data: { id: 7, image_url: "http://localhost/storage/products/a.png" } });
  };
  assert.deepEqual(await uploadProductImage(7, file), {
    id: 7, image_url: "http://localhost/storage/products/a.png",
  });
});

test("upload errors expose the field message and oversized files are explained", async () => {
  const file = new File(["x"], "nota.txt");
  globalThis.fetch = async () => Response.json({ errors: { image: ["Arquivo inválido."] } }, { status: 422 });
  await assert.rejects(uploadProductImage(7, file), error => error.status === 422 && error.errors.image[0] === "Arquivo inválido.");
  globalThis.fetch = async () => new Response("<html>413</html>", { status: 413 });
  await assert.rejects(uploadProductImage(7, file), error => error.status === 413 && error.message.includes("grande demais"));
});

test("JSON writes keep their content type", async () => {
  globalThis.fetch = async (path, options) => {
    assert.equal(options.headers["Content-Type"], "application/json");
    assert.equal(options.body, JSON.stringify({ quantity: 2 }));
    return Response.json({ data: {} });
  };
  await updateCartItem(3, 2);
});
