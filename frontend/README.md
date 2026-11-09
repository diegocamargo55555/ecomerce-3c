# 3C Commerce

Interface em Vue 3 para a API Laravel existente, com vitrine minimalista em azul e laranja. A tela inicial exibe produtos sem exigir login. Produtos e categorias podem ser consultados por visitantes; as funções de gestão são exclusivas de admins.

## Executar

### Com Docker

Siga a [inicialização na raiz](../README.md), incluindo ambiente, migrations e seeder. Para subir todos os serviços necessários:

```sh
docker compose up -d --build
```

Abra **http://localhost/**. O Nginx encaminha API, Sanctum e imagens ao PHP e as demais chamadas ao Vite. As portas 5173 e 8000 não são publicadas. Os arquivos locais ficam montados para atualização automática, com `node_modules` isolado em um volume do Docker.

```sh
docker compose exec frontend npm test
docker compose exec frontend npm run build
docker compose logs -f frontend
```

Ao alterar dependências, reconstrua a imagem e renove o volume de `node_modules`:

```sh
docker compose up -d --build --renew-anon-volumes --no-deps frontend
```

### Desenvolvimento e build

O fluxo documentado usa Docker e o Nginx na porta 80. O Vite mantém um proxy opcional para execução fora do Compose, configurável por `API_PROXY_TARGET` e `API_SESSION_ORIGIN`; nesse modo, o backend e os domínios do Sanctum precisam ser configurados para a origem utilizada.

```sh
npm test
npm run build
```

## Funcionalidades

- Vitrine de produtos em cards, com busca por nome, descrição e categoria, filtro por categoria, ordenação por preço/nome/recência e paginação pela API com oito produtos por página.
- Detalhes de produtos em diálogo, usando os dados já recebidos da listagem. Imagens ausentes ou inválidas exibem um placeholder; produtos inativos ficam ocultos; produtos ativos sem estoque permanecem sinalizados no catálogo.
- Login, cadastro, restauração e encerramento da sessão com Sanctum.
- Visão geral exclusiva do admin, com dados reais de pedidos, produtos, estoque e pagamentos.
- Listagem, criação, edição e exclusão de produtos, categorias, pessoas, endereços, carrinhos, itens dos carrinhos, pedidos, itens dos pedidos e pagamentos.
- Busca, filtros, ordenação e paginação locais; exportação CSV dos resultados filtrados.
- Loading, estados vazios, erros por campo, nova tentativa e confirmação de exclusão.
- Layout responsivo, navegação por teclado e formulários em diálogos nativos.

## Integração existente

O cliente usa exclusivamente as rotas existentes:

| Recurso      | Endpoint                                                                                                                                                |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CSRF         | `GET /sanctum/csrf-cookie`                                                                                                                              |
| Autenticação | `POST /api/login`, `POST /api/register`, `GET /api/me`, `POST /api/logout`                                                                              |
| Recursos     | `/api/products`, `/api/categories`, `/api/users`, `/api/addresses`, `/api/carts`, `/api/cart-items`, `/api/orders`, `/api/order-items`, `/api/payments` |

Os recursos usam `GET` para listagem, `POST` para criação e `PATCH` / `DELETE` em `/:id`. O cliente aceita os registros de criação sem envelope e os Resources com `{ data: ... }` retornados pela API. Não há registros fictícios nem persistência paralela no navegador.

As listagens `GET /api/products` e `GET /api/categories` são públicas. A listagem pública de produtos aceita filtros e devolve `data`, `links` e `meta`; a gestão usa `GET /api/admin/products`, incluindo inativos. Os endpoints individuais exigem sessão Sanctum. Alterações no catálogo e todos os demais recursos exigem admin, além da sessão. A vitrine não exibe botões de criação, edição ou exclusão.

As rotas `#products` e `#categories` abrem a vitrine; `#login` permite entrar ou criar uma conta cliente. Após autenticar, clientes vão ao catálogo e admins à visão geral (`#overview`). Clientes que abrem telas de gestão voltam ao catálogo com aviso; visitantes vão ao login. Na gestão, `#manage-products` e `#manage-categories` reutilizam as tabelas e formulários existentes. Busca e categoria selecionada são reiniciadas ao recarregar a página. Não há carrinho de compras público, checkout ou promoções simuladas.

Pedidos aceitam `pendente`, `pago`, `enviado`, `entregue` e `cancelado`; pagamentos aceitam `pendente`, `pago` e `cancelado`. Endereços e pedidos usam `users_id`; carrinhos usam `user_id`. Como a resposta de endereço não inclui `users_id`, esse campo é enviado apenas no cadastro, preservando o vínculo na edição. O complemento é obrigatório no cadastro, conforme o contrato atual. Pagamentos são registros da API; não há processamento financeiro externo. Pedidos, seus itens e pagamentos são cadastrados separadamente, pois a API não oferece checkout transacional.

A API pode impedir a exclusão de registros vinculados. Produtos usam exclusão lógica: uma categoria ainda pode ter vínculos mesmo depois de seus produtos deixarem de aparecer na listagem. A interface informa a falha sem alterar as regras do backend.

Na vitrine, busca, filtro por categoria, ordenação e paginação são executados no servidor. As tabelas administrativas continuam com filtros e paginação locais; seus relacionamentos usam as listagens administrativas completas.

## Proxy e cookies

No Compose, `proxy/default.conf` encaminha `/api/`, `/sanctum/`, `/images/` e `/storage/` para `php:8000` e o restante, incluindo WebSocket, para `frontend:5173`. O navegador usa somente **http://localhost**. O Laravel aceita essa origem no Sanctum; os cookies são HttpOnly, SameSite=Lax, sem domínio explícito e compatíveis com HTTP local. O cliente usa cookies de sessão e XSRF, com renovação após 419, e não armazena senha nem token de autenticação em localStorage.

O build estático sozinho não inclui o proxy. Para hospedar `dist/`, configure o servidor com os mesmos encaminhamentos e os cookies para a origem de destino.
