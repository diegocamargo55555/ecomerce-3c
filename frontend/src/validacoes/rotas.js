export function isKnownRoute(key, resources) {
  return Object.hasOwn(resources, key)
    || /^products\/[^/]+$/.test(key)
    || /^order\/[^/]+$/.test(key)
    || ["login", "cart", "checkout", "my-orders", "profile", "overview", "manage-products", "manage-categories"].includes(key);
}
