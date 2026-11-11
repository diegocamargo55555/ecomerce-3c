# 40K

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
- Página pública de produto em `/#products/ID`, consultando o detalhe na API, com seletor de quantidade e adição ao carrinho para visitantes e clientes. Imagens ausentes ou inválidas exibem um placeholder; produtos inativos ficam ocultos; produtos ativos sem estoque permanecem sinalizados no catálogo.
- Login, cadastro, restauração e encerramento da sessão com Sanctum, com fusão do carrinho visitante ao autenticar.
- Carrinho público em `#cart`, com subtotais e total calculados no servidor, edição de quantidades, remoção e avisos de indisponibilidade.
- Visão geral exclusiva do admin, com dados reais de pedidos, produtos, estoque e pagamentos.
- Listagem, criação, edição e exclusão de produtos, categorias, pessoas, endereços, carrinhos e itens dos carrinhos. Pedidos, itens dos pedidos e pagamentos têm consulta administrativa, preservando os registros do checkout.
- Checkout em `#checkout`, com endereço próprio, revisão, frete fixo de R$ 20,00 e criação transacional do pedido. Detalhe em `#order/ID`, com confirmação de pagamento simulado pelo dono, sem dados de cartão.
- Busca, filtros, ordenação e paginação locais.
- Loading, estados vazios, erros por campo, nova tentativa e confirmação de exclusão.
- Layout responsivo, navegação por teclado e formulários em diálogos nativos.

## Organização do código

O JavaScript de comportamento está separado em três pastas dentro de `src/`:

```text
src/
├── validacoes/
│   ├── arquivos.js        # Limite de tamanho do arquivo escolhido
│   ├── produtos.js        # Disponibilidade e quantidade permitida
│   ├── respostas.js       # Estrutura das listas e da paginação da API
│   └── rotas.js           # Reconhecimento das rotas da aplicação
├── requisicoes/
│   ├── auth/             # Login, cadastro, sessão atual e logout
│   ├── http.js           # Fetch, cookies, CSRF, timeout e erros HTTP
│   ├── carrinho.js
│   ├── checkout.js
│   ├── pedidos.js
│   ├── perfil.js
│   ├── produtos.js
│   ├── recursos.js       # Listagem, criação, edição e exclusão administrativa
│   └── visaoGeral.js
└── manipulacao/
    ├── formatacao.js     # Moeda, datas, iniciais, relacionamentos e status
    ├── useApp.js         # Navegação, sessão, menu e notificações
    ├── useAuth.js
    ├── useCart.js
    ├── useCatalog.js
    ├── useCheckout.js
    ├── useModal.js
    ├── useMyOrders.js
    ├── useOrder.js
    ├── useOverview.js
    ├── useProduct.js
    ├── useProfile.js
    └── useResource.js
```

- **Validações:** verificam os dados e devolvem `true` ou `false`. Por exemplo, `isValidQuantity(quantity, product)` confere se a quantidade é inteira, positiva e cabe no estoque. Essas funções podem ser reutilizadas e não alteram o estado da tela. Os atributos de validação dos formulários (`required`, `min`, `max`, etc.) continuam definidos nos templates e na configuração dos campos em `resources.js`; a API mantém a validação final.
- **Requisições:** sabem qual endpoint chamar, qual método usar e quais dados enviar. Toda chamada passa por `http.js`, que mantém o tratamento de sessão, CSRF e falhas. As respostas continuam com os mesmos formatos esperados pelas telas.
- **Manipulação:** reúne o estado reativo e as ações de cada tela: abrir formulários, aplicar filtros, exibir carregamento, chamar as requisições e atualizar os dados apresentados. As funções `use...` são composições do Vue, chamadas no `<script setup>` do componente. Cada chamada cria seu próprio estado, para que formulários e erros não sejam compartilhados entre instâncias.

Os componentes `.vue` importam essas composições e recebem as variáveis e funções utilizadas no template. Continuam responsáveis pelo HTML, pelos estilos, pelas propriedades e pelos eventos. `Icon.vue` contém apenas a definição visual dos ícones. `resources.js` concentra a configuração dos recursos, campos, colunas e navegação.

### Variáveis preservadas

Os nomes das variáveis foram mantidos. Suas declarações de estado agora ficam na composição da tela correspondente e os componentes recebem as mesmas referências reativas.

| Variável | Para que serve |
| --- | --- |
| `form` | Guarda os valores preenchidos no formulário. |
| `originalForm` | Guarda os valores anteriores para enviar somente os campos alterados na edição administrativa. |
| `loading`, `starting` | Indicam que os dados ou a sessão inicial estão sendo carregados. |
| `busy`, `saving`, `placing` | Indicam que uma ação está em andamento e controlam os botões. |
| `error`, `formError`, `loadError` | Guardam mensagens gerais de falha. |
| `errors`, `fieldErrors` | Guardam mensagens de erro por campo retornadas pela API. |
| `search`, `category`, `filter`, `sort`, `page` | Controlam busca, filtros, ordenação e paginação. |
| `rows`, `products`, `cart`, `order` | Guardam os dados exibidos na tela correspondente. |
| `editing`, `deleting`, `modalOpen` | Controlam o registro selecionado e a abertura dos diálogos. |
| `user`, `notice`, `toast` | Guardam a conta atual e as mensagens apresentadas ao usuário. |

Por exemplo, ao adicionar um produto ao carrinho: `ProductView.vue` chama `add()` de `manipulacao/useProduct.js`; essa ação consulta `validacoes/produtos.js`, chama `addToCart()` de `requisicoes/carrinho.js` e atualiza `saving`, `cartError` e `added`. A separação permite encontrar e alterar cada responsabilidade sem misturar a regra de quantidade, o endereço da API e a atualização da interface.

## Integração existente

O cliente usa exclusivamente as rotas existentes:

| Recurso      | Endpoint                                                                                                                                                |
| ------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------- |
| CSRF         | `GET /sanctum/csrf-cookie`                                                                                                                              |
| Autenticação | `POST /api/login`, `POST /api/register`, `GET /api/me`, `POST /api/logout`                                                                              |
| Recursos     | `/api/products`, `/api/categories`, `/api/users`, `/api/addresses`, `/api/carts`, `/api/cart-items`, `/api/orders`, `/api/order-items`, `/api/payments` |
| Checkout | `GET /api/checkout`, `POST /api/orders` |
| Endereços da conta | `GET/POST /api/my/addresses` |
| Perfil | `GET/PATCH /api/me` |
| Edição e exclusão de endereços próprios | `PATCH/DELETE /api/my/addresses/:id` |
| Status administrativo | `PATCH /api/admin/orders/:id/status` |
| Histórico e cancelamento | `GET /api/my/orders`, `POST /api/orders/:id/cancel` |
| Detalhe e pagamento | `GET /api/orders/:id`, `POST /api/orders/:id/pay` |

Os recursos usam `GET` para listagem, `POST` para criação e `PATCH` / `DELETE` em `/:id`. O cliente aceita os registros de criação sem envelope e os Resources com `{ data: ... }` retornados pela API. Não há registros fictícios nem persistência paralela no navegador.

As listagens `GET /api/products` e `GET /api/categories` são públicas. A listagem pública de produtos aceita filtros e devolve `data`, `links` e `meta`; a gestão usa `GET /api/admin/products`, incluindo inativos. O detalhe de produto é público e retorna 404 para produtos inexistentes, inativos ou excluídos. O detalhe de categoria exige sessão Sanctum. Alterações no catálogo e os CRUDs administrativos exigem admin, além da sessão. `GET /api/cart` consulta o carrinho atual; `POST /api/cart/items` adiciona, e `PATCH`/`DELETE /api/cart/items/:id` atualizam/removem itens. Visitantes e clientes usam cookies de sessão; escritas exigem CSRF. A vitrine não exibe botões de criação, edição ou exclusão.

As rotas `#products` e `#categories` abrem a vitrine; `#login` permite entrar ou criar uma conta cliente. Após autenticar, clientes vão ao carrinho (`#cart`) e admins à visão geral (`#overview`). Clientes que abrem telas de gestão voltam ao catálogo com aviso; visitantes vão ao login. Na gestão, `#manage-products` e `#manage-categories` reutilizam as tabelas e formulários existentes. Busca e categoria selecionada são reiniciadas ao recarregar a página. A página de produto permite adicionar ao carrinho persistido no servidor. A tela `#cart` permite consultar, atualizar e remover itens; os valores e o contador vêm da API. Login e cadastro fundem o carrinho visitante com o da conta e exibem os ajustes de estoque retornados em `cart_warnings`. O botão “Finalizar compra” abre `#checkout`; visitantes são direcionados ao login e retornam após a fusão. O pedido criado abre `#order/ID`, onde seu dono confirma o pagamento simulado.

Pedidos aceitam `pendente`, `pago`, `enviado`, `entregue` e `cancelado`; pagamentos aceitam `pendente`, `pago` e `cancelado`. Endereços e pedidos usam `users_id`; carrinhos usam `user_id`. Como a resposta de endereço não inclui `users_id`, esse campo é enviado apenas no cadastro administrativo, preservando o vínculo na edição. No checkout, o vínculo é atribuído pelo servidor à conta autenticada. O complemento do endereço é opcional. Pagamentos são registros da API; não há processamento financeiro externo. Pedidos, itens e pagamentos são criados pelo checkout transacional; suas tabelas no painel permanecem para consulta. A API também oferece criação e edição administrativas controladas de itens e pagamentos, com estoque e totais recalculados, conforme o [README da raiz](../README.md#checkout-e-pagamento-simulado). Preços e nomes são congelados no pedido, o estoque é baixado na criação e o pagamento altera o status sem baixar estoque novamente. O histórico em `#my-orders` lista somente os pedidos da conta. No detalhe, o dono ou um admin pode cancelar pedidos pendentes com confirmação em modal e devolução de estoque. Admins abrem o detalhe pelo painel e usam “Marcar como enviado” ou “Marcar como entregue”, conforme o status atual. A API permite apenas pago → enviado → entregue; conflitos recarregam o detalhe.

A API pode impedir a exclusão de registros vinculados. Produtos usam exclusão lógica: uma categoria ainda pode ter vínculos mesmo depois de seus produtos deixarem de aparecer na listagem. A interface informa a falha sem alterar as regras do backend.

Na vitrine, busca, filtro por categoria, ordenação e paginação são executados no servidor. As tabelas administrativas continuam com filtros e paginação locais; seus relacionamentos usam as listagens administrativas completas.

## Proxy e cookies

No Compose, `proxy/default.conf` encaminha `/api/`, `/sanctum/`, `/images/` e `/storage/` para `php:8000` e o restante, incluindo WebSocket, para `frontend:5173`. O navegador usa somente **http://localhost**. O Laravel aceita essa origem no Sanctum; os cookies são HttpOnly, SameSite=Lax, sem domínio explícito e compatíveis com HTTP local. O cliente usa cookies de sessão e XSRF, com renovação após 419, e não armazena senha nem token de autenticação em localStorage.

O build estático sozinho não inclui o proxy. Para hospedar `dist/`, configure o servidor com os mesmos encaminhamentos e os cookies para a origem de destino.

## Perfil e endereços

`#profile` exige sessão e reúne nome, e-mail e endereços da conta. Alterar e-mail exige a senha atual; os dados atualizados aparecem no cabeçalho sem sair da conta. É possível cadastrar, editar e excluir endereços com erros por campo e confirmação de exclusão. A API pode retornar outro ID ao editar um endereço usado em pedidos; a tela substitui o registro pelo retornado, preservando o endereço original das compras anteriores. A exclusão retira o endereço da lista de entrega sem remover o histórico. A coluna de exclusão lógica dos endereços está na migration original; consulte o README da raiz para recriar bases de desenvolvimento antigas.
