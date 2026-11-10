export function isResourceList(data) {
  return Array.isArray(data);
}

export function isCatalogResponse(response) {
  return Array.isArray(response?.data)
    && Number.isInteger(response?.meta?.total)
    && Number.isInteger(response?.meta?.last_page);
}
