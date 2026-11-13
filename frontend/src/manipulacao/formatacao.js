export const statusNames = {
  pendente: "Pendente",
  pago: "Pago",
  enviado: "Enviado",
  entregue: "Entregue",
  cancelado: "Cancelado",
};

export const currency = (value) =>
  new Intl.NumberFormat("pt-BR", { style: "currency", currency: "BRL" }).format(
    Number(value) || 0,
  );

export const date = (value) =>
  value && !Number.isNaN(new Date(value).getTime())
    ? new Intl.DateTimeFormat("pt-BR", { dateStyle: "short" }).format(
        new Date(value),
      )
    : "—";
export const initials = (value = "") =>
  value
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0])
    .join("")
    .toUpperCase() || "40K";

export function relationLabel(key, record) {
  if (key === "addresses")
    return `${record.recipient_name} · ${record.street}, ${record.number}`;
  if (key === "orders" || key === "carts")
    return `#${String(record.id).padStart(4, "0")}`;
  return record.name || `#${record.id}`;
}
