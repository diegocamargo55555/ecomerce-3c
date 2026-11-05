# Frontend — Loja 3C

Vue 3, JavaScript, Vue Router e CSS próprio. A interface está em português e se adapta a celular, tablet e computador.

```sh
npm ci
npm run dev
```

Acesse http://localhost:5173 com a API Laravel na porta 8000. Configure `API_PROXY_TARGET` em `.env` se necessário. O proxy do Vite mantém cookies e CSRF na mesma origem.

```sh
npm run build
npm run preview
```

- `src/views`: catálogo, produto, acesso, carrinho, checkout, pedidos e administração.
- `src/components`: campos, ícones, cards, mensagens e resumo do pedido.
- `src/stores/shop.js`: sessão, carrinho e notificações reativas.
- `src/api/http.js`: integração HTTP, cookies, CSRF e tratamento de erros.
- `src/router`: rotas e proteção das áreas autenticadas e administrativas.
- `src/assets/main.css`: estilos, componentes e ajustes responsivos.

Para executar tudo com Docker, conceder acesso administrativo e rodar os testes da API, consulte o README na raiz do projeto.
