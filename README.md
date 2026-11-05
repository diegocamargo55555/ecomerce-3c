# Loja 3C

Loja em Vue 3 e Laravel com catálogo público, cadastro/login, carrinho persistente, finalização com endereço, histórico de pedidos e administração de produtos, categorias e pedidos.

A versão registra pedidos pendentes, sem cobrança real. O frete é gratuito. Administradores podem marcar pedidos pendentes como pagos ou cancelados; o cancelamento devolve os itens ao estoque. Estados finais não podem ser alterados.

## Executar com Docker

```sh
docker compose up -d --build
docker compose exec php php artisan migrate
```

Se o backend ainda não tiver configuração, copie `Backend/.env.example` para `Backend/.env` antes de subir e gere a chave com `docker compose exec php php artisan key:generate`. Em um banco vazio, carregue os exemplos com `docker compose exec php php artisan db:seed`.

Abra **http://localhost**. O proxy atende o Vue e encaminha `/api` e `/sanctum` para o Laravel na mesma origem. Os volumes existentes do MySQL são preservados. Após alterar o front, execute `docker compose up -d --build frontend`.

Cadastre uma conta pela interface e conceda acesso administrativo pelo terminal:

```sh
docker compose exec php php artisan app:make-admin seu@email.com
```

O cadastro público sempre cria clientes. A conta administrativa passa a ter o link “Administrar loja” no menu da conta; atualize a página após conceder a permissão.

## Desenvolver sem reconstruir o front

Com o backend na porta 8000:

```sh
cd frontend
npm ci
npm run dev
```

Abra **http://localhost:5173**. Node `^22.18.0` ou `>=24.12.0` é necessário. O Vite encaminha as chamadas ao backend; para outro endereço, configure `API_PROXY_TARGET` no `frontend/.env` conforme o exemplo. Use sempre o mesmo host durante a navegação (`localhost` ou `127.0.0.1`). Se `SANCTUM_STATEFUL_DOMAINS` estiver definido no ambiente do backend, inclua o host e a porta do front.

Também é possível executar o Laravel localmente com PHP 8.2+, Composer e o banco configurado em `Backend/.env`:

```sh
cd Backend
composer install
php artisan key:generate # Somente se APP_KEY ainda estiver vazio.
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

## Verificar

```sh
cd frontend
npm run build
```

```sh
cd Backend
php artisan test
```

Os testes da API usam SQLite em memória, sem modificar o banco da aplicação. Cobrem catálogo público, autenticação, permissões, isolamento entre clientes, carrinho, estoque, confirmação duplicada e atualização dos pedidos.

## Contratos principais

- `GET /api/products` e `/api/categories`: consulta pública; produtos inativos não aparecem.
- `GET /api/cart`: recupera ou inicia o carrinho do cliente, incluindo itens e produtos.
- `POST /api/cart-items`: recebe `cart_id`, `product_id` e `quantity`; adiciona a quantidade ao item existente. `PATCH /api/cart-items/:id` define a quantidade e `DELETE` remove o item.
- `GET/POST /api/addresses`: endereços do cliente autenticado; o proprietário é definido no servidor.
- `POST /api/checkout`: recebe somente `address_id`. Calcula preços, valida estoque, cria pedido e itens, baixa estoque e limpa o carrinho em uma transação.
- `GET /api/orders`: pedidos do cliente com itens e endereço.
- `/api/admin/products`, `/api/admin/categories` e `/api/admin/orders`: exigem administrador. Pedidos aceitam somente a transição de pendente para pago ou cancelado.
- `GET /api/me`: retorna `id`, `name`, `email` e `is_admin`. Autenticação por sessão Sanctum e CSRF, sem tokens no armazenamento do navegador.

Na administração, imagens são cadastradas por URL e produtos podem ser desativados. Categorias com produtos vinculados não podem ser excluídas. Endereços usados em pedidos são preservados; para uma nova entrega, cadastre outro endereço.
