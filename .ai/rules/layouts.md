---
paths:
  - 'resources/js/pages/**,resources/js/app.ts,resources/js/layouts/**'
---

# Layouts

## Layout declarado na page (nunca resolução global em app.ts)
app.ts NÃO tem callback `layout` no createInertiaApp. Cada page declara `defineOptions({ layout: AuthLayout })` (ou aninhado `[AppLayout, SettingsLayout]`) e passa props via `setLayoutProps` do @inertiajs/vue3. setLayoutProps recebe CHAVES de tradução (`title: 'app.auth.login.title'`, `breadcrumbs: [{ title: 'app.nav.dashboard', href: dashboard() }]`) — os wrappers resources/js/layouts/AuthLayout.vue e AppLayout.vue traduzem com t(). Hrefs sempre via Wayfinder, nunca URL hardcoded. Valores reativos em layoutProps: watchEffect/computed. Landing pages públicas (Welcome) podem ficar sem layout.
