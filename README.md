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

O campo `role` é definido diretamente em `0001_01_01_000000_create_users_table.php`, limitado a `cliente` e `admin`, com padrão `cliente`. Alterar essa migration não atualiza tabelas já criadas: bancos antigos sem `role` precisam de ajuste do schema antes de usar esta versão. Instalações novas já recebem essa coluna.

O seeder isolado cria `admin@example.com` com senha `password` somente em ambiente `local` ou `testing`. Se a conta já for admin, preserva seus dados e senha. Se o e-mail pertencer a um cliente, interrompe sem promover ou sobrescrever a conta. O `DatabaseSeeder` pode ser repetido: cria somente os exemplos ausentes, sem redefinir preços, estoques, senhas ou registros já cadastrados. Produtos excluídos logicamente não são recriados. A carga roda em uma transação.

Credenciais de demonstração, exclusivamente locais:

| E-mail | Senha inicial | Papel |
| --- | --- | --- |
| admin@example.com | password | admin |
| test@example.com | password | cliente |
| ana@example.com | password | cliente |
| bruno@example.com | password | cliente |

Abra **http://localhost/#login** para entrar ou cadastrar um cliente. Clientes são direcionados ao carrinho após login ou cadastro; admins, à visão geral. A sessão é consultada antes de liberar telas internas, inclusive após recarregar. Clientes que abrem um hash administrativo voltam ao catálogo com aviso; visitantes vão ao login. O cabeçalho exibe gestão somente para admins e permite sair da conta.

## Proxy, sessão e permissões

`proxy/default.conf` é a configuração do Nginx montada pelo Compose. `/api/`, `/sanctum/`, `/images/` e `/storage/` vão para `php:8000`; os demais caminhos vão para `frontend:5173`, incluindo o WebSocket do Vite. As portas 8000 e 5173 ficam apenas na rede dos containers. O proxy aguarda o PHP saudável e o frontend iniciado. O volume do MySQL e os arquivos de imagens/storage montados em `Backend/` são preservados ao recriar containers; não use `docker compose down -v` para atualizar.

O navegador usa caminhos relativos na origem pública. O Nginx preserva Cookie, Set-Cookie, Origin e Referer e encaminha Host e os cabeçalhos de proxy. O Laravel valida sessão e CSRF: antes de escrever, o cliente consulta `/sanctum/csrf-cookie`, envia cookies com `credentials: "include"` e o token em `X-XSRF-TOKEN`. Após 419, renova o CSRF e repete a chamada uma única vez. Senhas e tokens de autenticação não são guardados em localStorage.

| Operação | Acesso |
| --- | --- |
| `GET /api/products`, `GET /api/categories`, `POST /api/register`, `POST /api/login` | Público |
| `GET /api/admin/products` (todos os produtos, incluindo inativos) | Admin |
| `GET/PATCH /api/me`, `POST /api/logout` | Cliente ou admin autenticado |
| `GET /api/products/:id` | Público |
| `GET /api/cart`, `POST /api/cart/items`, `PATCH /api/cart/items/:id`, `DELETE /api/cart/items/:id` | Carrinho atual da sessão, com ou sem login; escritas exigem CSRF |
| `GET /api/categories/:id` | Cliente ou admin autenticado |
| Criação, edição e exclusão de produtos/categorias | Admin |
| CRUD de users, addresses, carts e cart-items; consulta geral de pedidos, itens e pagamentos | Admin |
| `GET /api/checkout`, `POST /api/orders`, `GET/POST /api/my/addresses` | Cliente ou admin autenticado, usando o próprio carrinho e endereço |
| `GET /api/orders/:id` | Dono do pedido ou admin |
| `POST /api/orders/:id/pay` | Somente o dono do pedido |
| `PATCH/DELETE /api/my/addresses/:id` | Somente o proprietário, inclusive para admins |
| `GET /api/my/orders` | Pedidos da própria conta, inclusive para admins |
| `POST /api/orders/:id/cancel` | Dono ou admin; somente pedidos pendentes |
| `PATCH /api/admin/orders/:id/status` | Somente admin; pago → enviado → entregue |

Sem sessão, rotas protegidas retornam 401; clientes recebem 403 nas operações administrativas, inclusive acessando a API diretamente. A Gate `admin` protege as rotas no backend. Cadastro e criação de usuários produzem clientes; enviar `role` no cadastro, criação ou edição de usuários retorna 422, inclusive valores vazios. O papel é retornado em `UserResource` e não é mass assignable. Não há endpoint de promoção de papel.

## Verificação

```sh
docker compose exec php php artisan test
docker compose exec frontend npm test
docker compose exec frontend npm run build
docker compose exec proxy nginx -t
docker compose ps
```

Os testes de backend forçam SQLite em memória, mesmo dentro do Compose, e verificam catálogo público, filtros, paginação, autorização, dados dos seeders, carrinho, totais decimais, isolamento de itens e fusão no login/cadastro, além de repetição dos seeders sem sobrescrever registros. Os testes do frontend cobrem o contrato paginado do catálogo e o cliente HTTP, incluindo cookies e renovação de CSRF. O build estático em `frontend/dist` precisa de um servidor que encaminhe os mesmos caminhos da API e imagens.

Para conferir no navegador, abra o catálogo sem login, entre/crie uma conta cliente, recarregue, tente `#overview` e saia. Depois entre como admin e abra o painel. Na aba Network, requisições HTTP e WebSocket devem continuar em `localhost:80`; confira também imagens, cookies e o retorno 401 de `/api/me` após logout.

## Catálogo e dados de demonstração

`GET /api/products` retorna somente produtos ativos, em páginas de oito registros, com `data`, `links` e `meta`. Parâmetros: `search` (nome, descrição ou categoria), `category_id`, `sort` (`newest`, `price-asc`, `price-desc`, `name`), `page` e `per_page` (1 a 100). A vitrine usa os filtros e os totais da API. `GET /api/admin/products` mantém a lista completa para a gestão e seus relacionamentos.

Os seeders criam 30 produtos em quatro categorias: os oito produtos originais e 22 kits de demonstração, que reutilizam a foto de uma unidade e indicam isso na descrição. Há produto inativo, produto ativo sem estoque, três carrinhos de clientes e um de visitante populados, além de cinco pedidos com itens e pagamentos consistentes (`pendente` significa aguardando pagamento, seguido de `pago`, `enviado`, `entregue` e `cancelado`). O token do carrinho de demonstração deriva de uma HMAC com a `APP_KEY`; não é publicado nem deve ser usado como sessão fixa de visitantes reais.

Para completar uma base existente, preservando os dados:

```sh
docker compose exec php php artisan migrate
docker compose exec php php artisan db:seed
```

Os cinco status estão definidos diretamente na migration que cria `orders`. O checkout cria pedidos pendentes e a confirmação de pagamento os torna pagos; o admin pode avançar de pago para enviado e de enviado para entregue. Alterar a migration original não atualiza tabelas já criadas. Instalações novas já aceitam os cinco status.

## Imagens de produtos

No painel **Produtos**, o formulário de criação e edição tem o campo **Imagem do produto**: escolha um arquivo JPG, PNG ou WebP de até 2 MB. O campo de URL externa continua disponível; se os dois forem preenchidos, o arquivo enviado prevalece. O painel salva o produto e, em seguida, envia o arquivo; se o envio falhar, o produto permanece salvo e o formulário avisa.

`POST /api/admin/products/{id}/image` (somente admin) recebe o arquivo no campo `image` em `multipart/form-data`. O tipo é validado pelo conteúdo, o nome é gerado pelo servidor e `image_url` passa a apontar para `/storage/products/<nome>`. A imagem enviada anteriormente só é apagada quando nenhum outro produto, inclusive excluído logicamente, usa a mesma URL; URLs externas e as fotos dos seeders em `Backend/public/images/products` não são alteradas. Arquivo ausente, de outro tipo ou acima de 2 MB retorna 422; produto inexistente ou excluído retorna 404.

Os arquivos ficam em `storage/app/public`, montado no volume nomeado `product_images` do Compose, e por isso sobrevivem à recriação do container (`docker compose up -d --force-recreate php`). Eles só são apagados com `docker compose down -v`. Ao iniciar, o container executa `php artisan storage:link`, que cria `public/storage`; o Nginx já encaminha `/storage/` para a API e aceita corpos de até 5 MB (`client_max_body_size`). A URL gravada usa `APP_URL` como origem.

## Página pública do produto

Clique na imagem, no nome ou em “Ver detalhes” para abrir `/#products/ID`. O endereço pode ser compartilhado e recarregado sem login. A página consulta `GET /api/products/{id}` e exibe nome, imagem, descrição, categoria, preço e estoque. Produtos inexistentes, inativos ou excluídos retornam 404. Produtos ativos sem estoque continuam visíveis, com a compra desabilitada.

O botão “Adicionar ao carrinho” envia `product_id` e `quantity` para `POST /api/cart/items`. A API identifica a conta pela sessão autenticada ou gera um identificador aleatório guardado na sessão do visitante. O cliente não escolhe o carrinho. Quantidades repetidas são somadas no banco; a soma não pode ultrapassar o estoque e a quantidade deve ser inteira e positiva (422 em caso de erro). A operação usa transação e bloqueio dos registros, sem baixar estoque nesta etapa. O identificador de visitante não é retornado ao navegador; os cookies e o CSRF seguem o mesmo fluxo da autenticação.

## Carrinho e fusão no login

Abra **http://localhost/#cart** pelo ícone do cabeçalho ou pelo link após adicionar um produto. A tela mostra imagem, nome, preço atual, quantidade, subtotal por produto e total, com indicador da quantidade de unidades. Permite atualizar a quantidade e remover itens; quantidade zero também remove. Produtos inativos, excluídos, sem estoque ou com quantidade acima do estoque são sinalizados e podem ser removidos.

`GET /api/cart` devolve o carrinho atual com `items`, `item_count` e `total` dentro de `data`. `PATCH /api/cart/items/{id}` recebe apenas `quantity`; `DELETE /api/cart/items/{id}` remove o item. As duas mutações devolvem o carrinho atualizado. O servidor resolve o proprietário pela sessão, ignora valores financeiros e identificadores de carrinho enviados pelo navegador, e retorna 404 para itens de outro visitante ou usuário. Subtotais e total são calculados em decimal com BCMath, usando os preços atuais do banco, incluindo os itens sinalizados como indisponíveis. O total não inclui frete; o estoque só será baixado no checkout.

A regra de fusão, executada no login **e** no cadastro, é:

1. O servidor recupera o identificador de visitante guardado na sessão; não aceita um token informado no corpo da requisição.
2. Produtos presentes nos dois carrinhos têm suas quantidades somadas. Produtos presentes em apenas um são mantidos.
3. A fusão revalida todos os produtos dos dois carrinhos. A quantidade é limitada ao estoque atual; produtos inativos, excluídos ou sem estoque são removidos. Cada ajuste ou remoção gera um aviso em `cart_warnings`, ao lado de `data` na resposta de autenticação, exibido no frontend.
4. A fusão é transacional, descarta o carrinho visitante com seus itens, remove o identificador da sessão e regenera a sessão invalidando a anterior. Login ou cadastro inválido preserva o carrinho visitante.
5. O carrinho da conta permanece no banco entre sessões e dispositivos. Logout não apaga esse carrinho.

Requisições da mesma sessão são serializadas para evitar perda do identificador de visitante entre abas; alterações no banco usam bloqueio dos registros. As migrations que criam `carts` e `cart_items` já definem os índices únicos de `user_id` e de `cart_id` + `product_id`. Alterar essas migrations não atualiza tabelas existentes. Instalações novas já possuem esses índices; bases antigas sem eles precisam de ajuste do schema antes de usar esta versão.


## Checkout e pagamento simulado

No carrinho, clique em **Finalizar compra** para abrir `/#checkout`. Visitantes entram ou criam uma conta, têm o carrinho fundido e voltam ao checkout. Usuários autenticados podem selecionar um endereço próprio ou cadastrar um novo; o complemento é opcional. Não há cálculo de frete por CEP: o frete é fixo em **R$ 20,00 por pedido**, exibido na revisão e calculado exclusivamente no servidor.

`GET /api/checkout` retorna itens, quantidades, preços atuais, subtotais, frete e total. `POST /api/orders` recebe somente `address_id`; usuário, status e valores financeiros enviados no corpo são ignorados. O servidor verifica que o endereço pertence à conta, revalida todos os produtos e calcula tudo novamente com BCMath. Carrinho vazio, produto indisponível, estoque insuficiente ou total acima do limite `decimal(10,2)` retornam 422; o erro de produto informa qual item falhou. Endereço alheio retorna 404.

A criação usa uma transação com bloqueio do carrinho, dos itens e dos produtos em ordem de ID: grava o pedido pendente, congela nome/preço nos itens, decrementa estoque, cria o registro de pagamento pendente e esvazia o carrinho. Qualquer falha desfaz toda a operação. Duas confirmações simultâneas da mesma conta não duplicam o pedido; concorrência pelo último produto só permite uma compra.

A página `/#order/ID` mostra o pedido, seus itens congelados, endereço, frete e total. O dono encontra a ação **Confirmar pagamento simulado**. `POST /api/orders/{id}/pay` não recebe valor, dados de cartão ou gateway; em uma transação, usa o total gravado no pedido, registra a data/hora no pagamento e altera o pedido para pago. Uma segunda tentativa ou pedido fora de pendente retorna 409; outro usuário, inclusive admin de outro pedido, recebe 403. O pagamento não baixa estoque novamente. O dono e admins podem consultar o detalhe por `GET /api/orders/{id}`.

Pedidos, itens e pagamentos ficam disponíveis para consulta no painel, com link para o detalhe do pedido. As antigas escritas manuais desses registros foram desabilitadas na API e na interface para preservar estoque, preços e pagamentos; envio, entrega e cancelamento usam ações próprias descritas abaixo. A unicidade de `payments.order_id` está definida na migration original de pagamentos; instalações novas já recebem o índice. Bases antigas sem ele precisam desse ajuste, pois editar uma migration já executada não altera o schema.

Os testes de backend também verificam autorização, valores enviados pelo cliente, limites monetários, preços congelados, repetição do checkout e do pagamento e rollback em falhas depois da baixa de estoque ou da confirmação do pagamento. A verificação HTTP em banco MySQL temporário cobre cookies reais e concorrência entre sessões, sem modificar os dados de compras do banco principal.

## Meus pedidos e cancelamento

Abra **http://localhost/#my-orders** pelo link “Meus pedidos”. A lista mostra número, data, total e status, dos mais recentes aos mais antigos, sem filtros ou paginação. Visitantes vão ao login e retornam ao histórico após autenticar. `GET /api/my/orders` retorna `data` com somente os pedidos da conta autenticada; parâmetros de outro usuário não alteram o proprietário consultado. A listagem administrativa `GET /api/orders` continua exclusiva de admins.

No detalhe `#order/ID`, o dono ou um admin pode escolher **Cancelar pedido** e confirmar no modal. `POST /api/orders/{id}/cancel` não exige corpo: quantidades, valores e status vêm do banco. A ação aceita somente pedidos pendentes, devolve o estoque dos itens e marca pedido e pagamento como cancelados, preservando os valores históricos. Produtos inativos ou excluídos logicamente recebem o estoque sem serem reativados. Pedidos antigos sem pagamento recebem um registro cancelado com o total original.

A transação bloqueia pedido, pagamento, itens e produtos. Pagamento e cancelamento disputam o mesmo bloqueio do pedido: só uma ação pode vencer. Cancelar novamente ou cancelar pedido pago/enviado/entregue retorna 409, sem nova devolução. Sem sessão retorna 401, cliente que não é dono recebe 403, e pedido inexistente retorna 404. Falhas desfazem toda a operação. O cancelamento não repõe itens no carrinho. O endereço original é preservado pelo versionamento descrito abaixo.

Verifique com duas contas que o histórico é isolado; crie um pedido pendente, cancele e confira o estoque e o pagamento. Repita a chamada e tente pagar o pedido cancelado: ambas retornam 409. Os testes automatizados cobrem autorização, produtos excluídos, repetição e rollback. A verificação HTTP em MySQL temporário também confirmou seis disputas entre sessões (cancelamento duplo e pagamento contra cancelamento), com uma única ação vencedora e estoque consistente. SQLite não reproduz esses bloqueios.

## Gestão de status pelo administrador

No painel **Pedidos**, use os filtros por status e abra o número do pedido. No detalhe, admins encontram **Marcar como enviado** em pedidos pagos e **Marcar como entregue** em pedidos enviados. O status é atualizado na tela e fica disponível no histórico do cliente; ao voltar a **Todos os pedidos**, a listagem consulta os dados novamente.

`PATCH /api/admin/orders/{id}/status` recebe `{ "status": "enviado" }` ou `{ "status": "entregue" }` e retorna o pedido atualizado com itens, endereço e pagamento em `data`. Somente as transições `pago → enviado → entregue` são aceitas. Status conhecidos que tentem repetir, pular ou retroceder uma etapa retornam 409; status ausente ou desconhecido retorna 422. Sem sessão retorna 401, clientes recebem 403 mesmo sendo donos do pedido, e pedido inexistente retorna 404 para admins. Pagamento e cancelamento continuam em seus endpoints próprios.

A operação usa transação e bloqueio do pedido, compartilhando a proteção contra concorrência com pagamento e cancelamento. A mudança não modifica estoque, itens, endereço, valores ou registro de pagamento. O botão fica bloqueado durante a chamada; em 409, o detalhe é recarregado para refletir a alteração feita em outra sessão. Os testes cobrem autorização, transições inválidas e preservação dos dados; a verificação em MySQL temporário confirmou que duas sessões administrativas concorrentes processam cada etapa somente uma vez. Nenhuma migration adicional é necessária.

## Perfil e endereços da conta

Abra **http://localhost/#profile** pelo link “Perfil” ou pelo ícone da conta. Visitantes vão ao login e retornam ao perfil após autenticar. `GET /api/me` consulta a conta e `PATCH /api/me` permite editar nome e e-mail, sempre da sessão atual. O e-mail deve ser único; alterar o e-mail exige `current_password` com a senha atual. A senha não é alterada nem retornada. Campos `role`, `id` e `password` são rejeitados com 422. Alterar o e-mail limpa a marca anterior de verificação, preserva a sessão e atualiza o cabeçalho.

Na mesma tela, é possível cadastrar, editar e excluir os próprios endereços. `GET/POST /api/my/addresses` lista/cadastra; `PATCH/DELETE /api/my/addresses/{id}` edita/exclui. Endereço alheio, inexistente ou excluído retorna 404, inclusive para admins nessas rotas pessoais. `users_id` não é aceito em edições pessoais; o CRUD administrativo mantém sua autorização separada.

A coluna `addresses.deleted_at` é criada diretamente em `2026_10_22_133440_create_addresses_table.php`, com `$table->softDeletes()`. Não há migration adicional para essa coluna. Em uma base de desenvolvimento antiga que possa ser apagada, execute `docker compose exec php php artisan migrate:fresh --seed --force`: isso apaga todos os dados e recria as tabelas com os exemplos. O banco local foi recriado dessa forma. Endereços excluídos ficam ocultos na conta e no checkout, mas continuam disponíveis nos pedidos. Ao editar um endereço já utilizado, a API devolve uma nova versão com **outro ID** e arquiva a anterior na mesma transação. O frontend utiliza o ID devolvido; novas compras usam a versão atual, enquanto pedidos existentes conservam os dados originais. A mesma proteção vale para edição e exclusão administrativas. Seeders não restauram os endereços excluídos.

Os testes verificam isolamento entre contas, validação, senha atual na alteração de e-mail, manutenção da sessão, versionamento, checkout com o novo ID e rollback. Alterações no endereço feitas antes desta versão não podem ser reconstruídas retroativamente.

## Limites desta etapa

Clientes podem navegar no catálogo, abrir produtos por URL, adicionar itens ao carrinho e entrar/sair da conta. A listagem pública oculta produtos inativos e mantém produtos ativos sem estoque sinalizados como indisponíveis.

Os CRUDs administrativos de catálogo, usuários, endereços e carrinhos permanecem disponíveis. Pedidos, seus itens e pagamentos são gerados pelo fluxo de compra e consultados pelo painel. Não use as credenciais de demonstração em produção.

Mais detalhes: [frontend](frontend/README.md), [backend e fotos](Backend/README.md) e requisitos em `Projeto.pdf`.
