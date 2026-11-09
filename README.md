# 3C Commerce

Catálogo em Vue 3, API Laravel com sessão Sanctum e MySQL. A aplicação local é acessada por **http://localhost**, na porta 80 do Nginx.

## Inicialização

Requisitos: Docker e Docker Compose. Para uma instalação nova, na raiz do projeto:

```sh
cp Backend/.env.example Backend/.env # somente se o arquivo ainda não existir
# Em uma instalação nova:
docker compose up -d --build
docker compose exec php php artisan key:generate
docker compose exec php php artisan migrate
# Somente em uma base vazia: cria o catálogo, três clientes e o admin local.
docker compose exec php php artisan db:seed
```

Preserve uma `APP_KEY` já existente. Ela fica em `Backend/.env`, que não deve ser versionado. O Compose configura o banco pelo serviço `mysql` e define `APP_URL=http://localhost`, `SANCTUM_STATEFUL_DOMAINS=localhost` e cookies de sessão sem domínio explícito, HttpOnly, SameSite=Lax, sem Secure para HTTP local. A sessão dura 120 minutos de inatividade. Não é preciso criar um `.env` no frontend. `ASSET_URL`, se configurada no backend, também deve usar a origem pública.

Para atualizar uma instalação existente que já tenha a coluna `role`, preserve `Backend/.env` e os volumes:

```sh
docker compose up -d --build
docker compose exec php php artisan config:clear
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed --class=AdminUserSeeder
```

O campo `role` é definido diretamente em `0001_01_01_000000_create_users_table.php`, limitado a `cliente` e `admin`, com padrão `cliente`. Alterar essa migration não atualiza tabelas já criadas: bancos antigos sem `role` precisam de ajuste do schema antes de usar esta versão. O banco local já possui a coluna e foi preservado.

O seeder isolado cria `admin@example.com` com senha `password` somente em ambiente `local` ou `testing`. Se a conta já for admin, preserva seus dados e senha. Se o e-mail pertencer a um cliente, interrompe sem promover ou sobrescrever a conta. Não execute novamente o `DatabaseSeeder` numa base preenchida, pois os demais seeders inserem novos registros.

Credenciais de demonstração, exclusivamente locais:

| E-mail | Senha inicial | Papel |
| --- | --- | --- |
| admin@example.com | password | admin |
| test@example.com | password | cliente |
| ana@example.com | password | cliente |
| bruno@example.com | password | cliente |

Abra **http://localhost/#login** para entrar ou cadastrar um cliente. Clientes são direcionados ao catálogo; admins, à visão geral. A sessão é consultada antes de liberar telas internas, inclusive após recarregar. Clientes que abrem um hash administrativo voltam ao catálogo com aviso; visitantes vão ao login. O cabeçalho exibe gestão somente para admins e permite sair da conta.

## Proxy, sessão e permissões

`proxy/default.conf` é a configuração do Nginx montada pelo Compose. `/api/`, `/sanctum/`, `/images/` e `/storage/` vão para `php:8000`; os demais caminhos vão para `frontend:5173`, incluindo o WebSocket do Vite. As portas 8000 e 5173 ficam apenas na rede dos containers. O proxy aguarda o PHP saudável e o frontend iniciado. O volume do MySQL e os arquivos de imagens/storage montados em `Backend/` são preservados ao recriar containers; não use `docker compose down -v` para atualizar.

O navegador usa caminhos relativos na origem pública. O Nginx preserva Cookie, Set-Cookie, Origin e Referer e encaminha Host e os cabeçalhos de proxy. O Laravel valida sessão e CSRF: antes de escrever, o cliente consulta `/sanctum/csrf-cookie`, envia cookies com `credentials: "include"` e o token em `X-XSRF-TOKEN`. Após 419, renova o CSRF e repete a chamada uma única vez. Senhas e tokens de autenticação não são guardados em localStorage.

| Operação | Acesso |
| --- | --- |
| `GET /api/products`, `GET /api/categories`, `POST /api/register`, `POST /api/login` | Público |
| `GET /api/me`, `POST /api/logout` | Cliente ou admin autenticado |
| `GET /api/products/:id`, `GET /api/categories/:id` | Cliente ou admin autenticado |
| Criação, edição e exclusão de produtos/categorias | Admin |
| Todas as operações de users, addresses, carts, cart-items, orders, order-items e payments | Admin |

Sem sessão, rotas protegidas retornam 401; clientes recebem 403 nas operações administrativas, inclusive acessando a API diretamente. A Gate `admin` protege as rotas no backend. Cadastro e criação de usuários produzem clientes; enviar `role` no cadastro, criação ou edição de usuários retorna 422, inclusive valores vazios. O papel é retornado em `UserResource` e não é mass assignable. Não há endpoint de promoção de papel.

## Verificação

```sh
docker compose exec php php artisan test
docker compose exec frontend npm test
docker compose exec frontend npm run build
docker compose exec proxy nginx -t
docker compose ps
```

Os testes de backend usam SQLite em memória, sem alterar os dados da loja, e cobrem autenticação, permissões, bloqueio de alteração de papel, padrão e restrição de papéis no schema inicial e repetição/conflito do seeder. Os testes do frontend cobrem o catálogo e o cliente HTTP, incluindo cookies e renovação de CSRF. O build estático em `frontend/dist` precisa de um servidor que encaminhe os mesmos caminhos da API e imagens.

Para conferir no navegador, abra o catálogo sem login, entre/crie uma conta cliente, recarregue, tente `#overview` e saia. Depois entre como admin e abra o painel. Na aba Network, requisições HTTP e WebSocket devem continuar em `localhost:80`; confira também imagens, cookies e o retorno 401 de `/api/me` após logout.

## Limites desta etapa

Clientes podem navegar no catálogo e entrar/sair da conta. Perfil do cliente, carrinho público, fusão de carrinhos, checkout, pagamento e novas transições de pedidos permanecem pendentes. Os detalhes visuais de produtos continuam em modal e o endpoint de detalhe continua exigindo autenticação. As listagens públicas ainda incluem produtos inativos, sinalizados como indisponíveis.

Os CRUDs administrativos existentes foram preservados. Pedidos, itens e pagamentos ainda são registros separados, sem checkout transacional ou novas regras financeiras. Não use as credenciais de demonstração em produção.

Mais detalhes: [frontend](frontend/README.md), [backend e fotos](Backend/README.md) e [pendências](PENDENCIAS_PROJETO.md).
