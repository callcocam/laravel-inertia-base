---
paths:
  - 'lang/**,resources/js/**,app/**'
---

# Js

## Traduções: árvore app.* em pt_BR, nunca texto hardcoded
Locale pt_BR com fallback en. Strings da aplicação vivem em arquivos por grupo em lang/pt_BR/app/*.php (auth.php, nav.php, common.php, settings.php, ...) acessadas como `app.grupo.chave` — o MergingFileLoader (app/Support/Translation, registrado no AppServiceProvider) mescla o diretório sobre o grupo plano. Backend/e-mails sempre `__('app.grupo.chave')`; frontend sempre `t('app.grupo.chave')` do composable `useT` (resources/js/composables/useT.ts), que lê `page.props.translations` compartilhado pelo HandleInertiaRequests. Nunca hardcode texto visível em Vue ou PHP. Chave nova → editar o arquivo do grupo em lang/pt_BR/app/. Testes asserem via `__()`, nunca strings literais em idioma fixo.
