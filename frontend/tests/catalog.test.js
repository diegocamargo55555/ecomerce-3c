import { test } from "node:test";
import assert from "node:assert/strict";
import { catalogProducts } from "../src/resources.js";

const products = [
  {
    id: 1,
    category_id: 2,
    name: "Teclado",
    description: "Layout ABNT2",
    price: "100.00",
    is_active: true,
    stock: 3,
  },
  {
    id: 2,
    category_id: 3,
    name: "Camiseta",
    description: null,
    price: "20.00",
    is_active: false,
    stock: 0,
  },
  {
    id: 3,
    category_id: 2,
    name: "Mouse",
    description: "Sem fio",
    price: "9.90",
    is_active: true,
    stock: 0,
  },
];
const categories = [
  { id: 2, name: "Informática" },
  { id: 3, name: "Roupas" },
];
const select = (filters = {}, data = products) =>
  catalogProducts(data, categories, {
    search: "",
    category: "",
    sort: "newest",
    ...filters,
  }).map((row) => row.id);

test("catalog searches names, descriptions and categories, combining the category filter", () => {
  assert.deepEqual(select({ search: " TECLADO " }), [1]);
  assert.deepEqual(select({ search: "abnt2" }), [1]);
  assert.deepEqual(select({ search: "informática" }), [3, 1]);
  assert.deepEqual(select({ category: "3", search: "mouse" }), []);
  assert.deepEqual(select({ category: "2", search: "sem fio" }), [3]);
});

test("catalog orders numeric prices and names without changing the source", () => {
  assert.deepEqual(select({ sort: "price-asc" }), [3, 2, 1]);
  assert.deepEqual(select({ sort: "price-desc" }), [1, 2, 3]);
  assert.deepEqual(select({ sort: "name" }), [2, 3, 1]);
  assert.deepEqual(
    products.map((row) => row.id),
    [1, 2, 3],
  );
});

test("catalog keeps unavailable products and handles empty data and missing categories", () => {
  assert.deepEqual(select(), [3, 2, 1]);
  assert.deepEqual(select({}, []), []);
  assert.deepEqual(
    catalogProducts(products, [], {
      search: "mouse",
      category: "",
      sort: "newest",
    }).map((row) => row.id),
    [3],
  );
});
