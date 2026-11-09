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
# Cria os exemplos ausentes e preserva os registros existentes.
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

O seeder isolado cria `admin@example.com` com senha `password` somente em ambiente `local` ou `testing`. Se a conta já for admin, preserva seus dados e senha. Se o e-mail pertencer a um cliente, interrompe sem promover ou sobrescrever a conta. O `DatabaseSeeder` pode ser repetido: cria somente os exemplos ausentes, sem redefinir preços, estoques, senhas ou registros já cadastrados. Produtos excluídos logicamente não são recriados. A carga roda em uma transação.

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
| `GET /api/admin/products` (todos os produtos, incluindo inativos) | Admin |
| `GET /api/me`, `POST /api/logout` | Cliente ou admin autenticado |
| `GET /api/products/:id`, `POST /api/cart/items` | Público, com cookie de sessão para adicionar ao carrinho |
| `GET /api/categories/:id` | Cliente ou admin autenticado |
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

Os testes de backend forçam SQLite em memória, mesmo dentro do Compose, e verificam catálogo público, filtros, paginação, autorização da listagem administrativa, dados dos seeders, totais e repetição sem sobrescrever registros. Os testes do frontend cobrem o contrato paginado do catálogo e o cliente HTTP, incluindo cookies e renovação de CSRF. O build estático em `frontend/dist` precisa de um servidor que encaminhe os mesmos caminhos da API e imagens.

Para conferir no navegador, abra o catálogo sem login, entre/crie uma conta cliente, recarregue, tente `#overview` e saia. Depois entre como admin e abra o painel. Na aba Network, requisições HTTP e WebSocket devem continuar em `localhost:80`; confira também imagens, cookies e o retorno 401 de `/api/me` após logout.

## Catálogo e dados de demonstração

`GET /api/products` retorna somente produtos ativos, em páginas de oito registros, com `data`, `links` e `meta`. Parâmetros: `search` (nome, descrição ou categoria), `category_id`, `sort` (`newest`, `price-asc`, `price-desc`, `name`), `page` e `per_page` (1 a 100). A vitrine usa os filtros e os totais da API. `GET /api/admin/products` mantém a lista completa para a gestão e seus relacionamentos.

Os seeders criam 30 produtos em quatro categorias: os oito produtos originais e 22 kits de demonstração, que reutilizam a foto de uma unidade e indicam isso na descrição. Há produto inativo, produto ativo sem estoque, três carrinhos de clientes e um de visitante populados, além de cinco pedidos com itens e pagamentos consistentes (`pendente` significa aguardando pagamento, seguido de `pago`, `enviado`, `entregue` e `cancelado`). O token do carrinho de demonstração deriva de uma HMAC com a `APP_KEY`; não é publicado nem deve ser usado como sessão fixa de visitantes reais.

Para completar uma base existente, preservando os dados:

```sh
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
```

Os cinco status estão definidos diretamente na migration que cria `orders`. A validação e os formulários administrativos reconhecem esses valores; as regras de transição continuam pendentes. Alterar a migration original não atualiza tabelas já criadas. A base local já aceita os cinco status e foi preservada.

## Página pública do produto

Clique na imagem, no nome ou em “Ver detalhes” para abrir `/#products/ID`. O endereço pode ser compartilhado e recarregado sem login. A página consulta `GET /api/products/{id}` e exibe nome, imagem, descrição, categoria, preço e estoque. Produtos inexistentes, inativos ou excluídos retornam 404. Produtos ativos sem estoque continuam visíveis, com a compra desabilitada.

O botão “Adicionar ao carrinho” envia `product_id` e `quantity` para `POST /api/cart/items`. A API identifica a conta pela sessão autenticada ou gera um identificador aleatório guardado na sessão do visitante. O cliente não escolhe o carrinho. Quantidades repetidas são somadas no banco; a soma não pode ultrapassar o estoque e a quantidade deve ser inteira e positiva (422 em caso de erro). A operação usa transação e bloqueio dos registros, sem baixar estoque nesta etapa. O identificador de visitante não é retornado ao navegador; os cookies e o CSRF seguem o mesmo fluxo da autenticação.

A tela completa do carrinho, seus totais e a fusão no login ainda estão pendentes. Adições anônimas e autenticadas permanecem em carrinhos separados até a implementação da fusão.

## Limites desta etapa

Clientes podem navegar no catálogo, abrir produtos por URL, adicionar itens ao carrinho e entrar/sair da conta. Perfil do cliente, tela completa do carrinho, fusão de carrinhos, checkout, pagamento e novas transições de pedidos permanecem pendentes. A listagem pública oculta produtos inativos e mantém produtos ativos sem estoque sinalizados como indisponíveis.

Os CRUDs administrativos existentes foram preservados. Pedidos, itens e pagamentos ainda são registros separados, sem checkout transacional ou novas regras financeiras. Não use as credenciais de demonstração em produção.

Mais detalhes: [frontend](frontend/README.md), [backend e fotos](Backend/README.md) e requisitos em `Projeto.pdf`.
