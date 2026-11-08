# 3C Commerce

Interface de gestão em Vue 3 para a API Laravel existente. As alterações deste frontend ficam nesta pasta.

## Executar

Com o backend existente disponível na porta 8000, execute nesta pasta:

```sh
npm install
npm run dev
```

Abra **http://127.0.0.1:5173/**. Entre com uma conta existente ou use **Criar conta**.

```sh
npm test         # testes do cliente HTTP, CSRF e erros
npm run build   # gera dist/
npm run preview # serve o build em http://127.0.0.1:5174/
```

## Funcionalidades

- Login, cadastro, restauração e encerramento da sessão com Sanctum.
- Visão geral com dados reais de pedidos, produtos, estoque e pagamentos.
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

Os valores de status enviados são `pendente`, `pago` e `cancelado`. Endereços e pedidos usam `users_id`; carrinhos usam `user_id`. Como a resposta de endereço não inclui `users_id`, esse campo é enviado apenas no cadastro, preservando o vínculo na edição. O complemento é obrigatório no cadastro, conforme o contrato atual. Pagamentos são registros da API; não há processamento financeiro externo. Pedidos, seus itens e pagamentos são cadastrados separadamente, pois a API não oferece checkout transacional.

A API pode impedir a exclusão de registros vinculados. Produtos usam exclusão lógica: uma categoria ainda pode ter vínculos mesmo depois de seus produtos deixarem de aparecer na listagem. A interface informa a falha sem alterar as regras do backend.

As listas atuais não são paginadas no servidor. Busca, ordenação e paginação acontecem sobre os registros retornados, e os relacionamentos são resolvidos pelas listagens existentes.

## Proxy e cookies

O Vite encaminha `/api` e `/sanctum` para `http://127.0.0.1:8000`, mantendo o navegador na mesma origem do frontend. O proxy usa uma origem já aceita pela configuração atual do Sanctum; não exige mudar CORS, rotas ou arquivos do backend.

Opcionalmente, configure em `.env.local`, **nesta pasta**:

```dotenv
API_PROXY_TARGET=http://127.0.0.1:8000
API_SESSION_ORIGIN=http://127.0.0.1:8000
```

`API_SESSION_ORIGIN` deve ser uma origem já aceita pelo Sanctum existente. Se omitida, usa a origem de `API_PROXY_TARGET`. Nunca coloque a `APP_KEY` ou credenciais de banco no frontend. O cliente usa cookies de sessão e o token XSRF; não armazena senha nem token de autenticação em localStorage.

`npm run preview` também inclui o proxy. Para hospedar `dist/` em outro servidor, esse servidor precisa encaminhar `/api` e `/sanctum` para a API, preservando os cookies e a origem de sessão configurada. O build estático sozinho não inclui o servidor proxy.
