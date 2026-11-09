const text = (key, label, extra = {}) => ({
  key,
  label,
  type: "text",
  required: true,
  max: 255,
  ...extra,
});
const number = (key, label, extra = {}) => ({
  key,
  label,
  type: "number",
  required: true,
  min: 0,
  step: 1,
  ...extra,
});
const money = (key, label) =>
  number(key, label, { step: "0.01", max: 99999999.99 });
const relation = (key, label, resource, extra = {}) => ({
  key,
  label,
  type: "select",
  resource,
  required: true,
  ...extra,
});
const status = {
  key: "status",
  label: "Status",
  type: "select",
  required: true,
  default: "pendente",
  options: [
    ["pendente", "Pendente"],
    ["pago", "Pago"],
    ["cancelado", "Cancelado"],
  ],
};
const col = (key, label, format) => ({ key, label, format });

export const resources = {
  products: {
    title: "Produtos",
    singular: "produto",
    icon: "box",
    description: "Seu catálogo, organizado nos mínimos detalhes.",
    fields: [
      text("name", "Nome do produto"),
      relation("category_id", "Categoria", "categories"),
      text("description", "Descrição", {
        type: "textarea",
        max: undefined,
        wide: true,
      }),
      money("price", "Preço (R$)"),
      number("stock", "Estoque", { default: 0 }),
      text("image_url", "URL da imagem", {
        type: "url",
        required: false,
        wide: true,
      }),
      {
        key: "is_active",
        label: "Produto ativo no catálogo",
        type: "checkbox",
        default: true,
      },
    ],
    columns: [
      col("name", "Produto", "product"),
      col("category_id", "Categoria", "categories"),
      col("price", "Preço", "money"),
      col("stock", "Estoque", "stock"),
      col("is_active", "Status", "active"),
    ],
  },
  categories: {
    title: "Categorias",
    singular: "categoria",
    icon: "tag",
    description: "Dê a cada produto o seu lugar.",
    fields: [
      text("name", "Nome"),
      text("slug", "Identificador (slug)", { hint: "Ex.: casa-e-decoracao" }),
      text("description", "Descrição", {
        type: "textarea",
        required: false,
        max: undefined,
        wide: true,
      }),
    ],
    columns: [
      col("name", "Categoria", "strong"),
      col("slug", "Identificador"),
      col("description", "Descrição"),
      col("created_at", "Criada em", "date"),
    ],
  },
  orders: {
    title: "Pedidos",
    singular: "pedido",
    icon: "orders",
    description: "Acompanhe cada pedido, do início ao fim.",
    fields: [
      relation("users_id", "Cliente", "users"),
      relation("address_id", "Endereço", "addresses"),
      {
        ...status,
        options: [
          ["pendente", "Aguardando pagamento"],
          ["pago", "Pago"],
          ["enviado", "Enviado"],
          ["entregue", "Entregue"],
          ["cancelado", "Cancelado"],
        ],
      },
      money("subtotal", "Subtotal (R$)"),
      money("shipping_cost", "Frete (R$)"),
      money("total", "Total (R$)"),
    ],
    columns: [
      col("id", "Pedido", "id"),
      col("users_id", "Cliente", "users"),
      col("status", "Status", "status"),
      col("total", "Total", "money"),
      col("created_at", "Criado em", "date"),
    ],
    related: "order-items",
  },
  "order-items": {
    title: "Itens dos pedidos",
    singular: "item do pedido",
    icon: "orders",
    description: "Consulte e organize os produtos de cada pedido.",
    parent: "orders",
    fields: [
      relation("order_id", "Pedido", "orders"),
      relation("product_id", "Produto", "products"),
      text("product_name", "Nome do produto no pedido", { wide: true }),
      money("unit_price", "Preço unitário (R$)"),
      number("quantity", "Quantidade", { min: 1, default: 1 }),
      money("subtotal", "Subtotal (R$)"),
    ],
    columns: [
      col("order_id", "Pedido", "id"),
      col("product_name", "Produto", "strong"),
      col("unit_price", "Preço unitário", "money"),
      col("quantity", "Quantidade"),
      col("subtotal", "Subtotal", "money"),
    ],
  },
  carts: {
    title: "Carrinhos",
    singular: "carrinho",
    icon: "cart",
    description: "Veja os carrinhos e os itens que fazem parte deles.",
    fields: [
      relation("user_id", "Cliente", "users", { required: false }),
      text("guest_token", "Identificador do visitante", { required: false }),
    ],
    columns: [
      col("id", "Carrinho", "id"),
      col("user_id", "Cliente", "users"),
      col("guest_token", "Visitante"),
      col("created_at", "Criado em", "date"),
    ],
    related: "cart-items",
  },
  "cart-items": {
    title: "Itens dos carrinhos",
    singular: "item do carrinho",
    icon: "cart",
    description: "Gerencie os produtos e as quantidades nos carrinhos.",
    parent: "carts",
    fields: [
      relation("cart_id", "Carrinho", "carts"),
      relation("product_id", "Produto", "products"),
      number("quantity", "Quantidade", { min: 1, default: 1 }),
    ],
    columns: [
      col("cart_id", "Carrinho", "id"),
      col("product_id", "Produto", "products"),
      col("quantity", "Quantidade"),
      col("created_at", "Adicionado em", "date"),
    ],
  },
  payments: {
    title: "Pagamentos",
    singular: "pagamento",
    icon: "wallet",
    description: "Tenha clareza sobre os pagamentos registrados.",
    fields: [
      relation("order_id", "Pedido", "orders"),
      status,
      money("amount", "Valor (R$)"),
      {
        key: "paid_at",
        label: "Data do pagamento",
        type: "datetime-local",
        required: false,
      },
    ],
    columns: [
      col("id", "Pagamento", "id"),
      col("order_id", "Pedido", "id"),
      col("amount", "Valor", "money"),
      col("status", "Status", "status"),
      col("paid_at", "Pago em", "date"),
    ],
  },
  users: {
    title: "Pessoas",
    singular: "pessoa",
    icon: "users",
    description: "Os contatos que fazem parte da sua loja.",
    fields: [
      text("name", "Nome completo"),
      text("email", "E-mail", { type: "email" }),
      text("password", "Senha", {
        type: "password",
        minLength: 8,
        max: undefined,
        optionalOnEdit: true,
        wide: true,
        hint: "No mínimo 8 caracteres. Ao editar, deixe em branco para manter a senha.",
      }),
    ],
    columns: [
      col("name", "Nome", "person"),
      col("email", "E-mail"),
      col("id", "Identificação", "id"),
    ],
  },
  addresses: {
    title: "Endereços",
    singular: "endereço",
    icon: "pin",
    description: "Mantenha os destinos de entrega sempre à mão.",
    fields: [
      relation("users_id", "Cliente", "users", { createOnly: true }),
      text("recipient_name", "Destinatário"),
      text("zip_code", "CEP"),
      text("street", "Rua / avenida"),
      text("number", "Número"),
      text("complement", "Complemento", {
        hint: "Campo obrigatório. Ex.: casa, bloco ou apartamento.",
      }),
      text("neighborhood", "Bairro"),
      text("city", "Cidade"),
      text("state", "Estado"),
    ],
    columns: [
      col("recipient_name", "Destinatário", "strong"),
      col("street", "Rua / avenida"),
      col("number", "Número"),
      col("city", "Cidade"),
      col("state", "Estado"),
      col("zip_code", "CEP"),
    ],
  },
};

export const navigation = [
  { label: "PRINCIPAL", items: ["overview", "products", "categories"] },
  { label: "OPERAÇÃO", items: ["orders", "carts", "payments"] },
  { label: "CADASTROS", items: ["users", "addresses"] },
];

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
    .toUpperCase() || "3C";

export function relationLabel(key, record) {
  if (key === "addresses")
    return `${record.recipient_name} · ${record.street}, ${record.number}`;
  if (key === "orders" || key === "carts")
    return `#${String(record.id).padStart(4, "0")}`;
  return record.name || `#${record.id}`;
}
