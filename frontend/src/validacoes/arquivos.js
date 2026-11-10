export function exceedsFileLimit(file, field) {
  return Boolean(file && field.maxBytes && file.size > field.maxBytes);
}
