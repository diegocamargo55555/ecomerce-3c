export function available(product) {
  return product.is_active && Number(product.stock) > 0;
}

export function isValidQuantity(quantity, product) {
  return Number.isInteger(quantity)
    && quantity >= 1
    && quantity <= product?.stock;
}
