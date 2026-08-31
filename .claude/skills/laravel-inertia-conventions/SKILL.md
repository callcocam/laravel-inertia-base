---
name: laravel-inertia-conventions
description: >-
  Convenções pessoais para projetos Laravel + Inertia + Vue (extraídas do
  projeto Estátuas Oliveira): layout declarado em cada page com
  defineOptions/setLayoutProps (nunca resolução global no app.ts), arquitetura
  de traduções pt_BR com árvore app.* (lang/pt_BR/app/*.php + MergingFileLoader
  + composable useT), e PKs ULID em vez de autoincrement. ATIVE ao iniciar ou
  estruturar um projeto Laravel/Inertia novo, criar pages Vue, exibir texto na
  UI/e-mails, criar migrations/models, ou ao encontrar $table->id()
  autoincrement. Inclui código de referência para implementar do zero.
---

# Convenções Laravel + Inertia + Vue

Três padrões a aplicar em qualquer projeto meu. Se o projeto já tiver uma skill própria (ex.: `.claude/skills/project-conventions`) ou `.ai/rules`, elas têm prioridade — esta skill serve para **implantar** os padrões em projetos que ainda não os têm.

## 1. Layout declarado na page (nunca em app.ts)

`resources/js/app.ts` não deve ter callback `layout` no `resolve`. Cada page declara o próprio layout (persistente — não re-monta entre navegações):

```ts
import AuthLayout from '@/layouts/AuthLayout.vue';
import { setLayoutProps } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

setLayoutProps({
    title: 'app.auth.login.title',        // chave de tradução — o layout traduz com t()
    description: 'app.auth.login.description',
});
```

- Layouts aninhados: `defineOptions({ layout: [AppLayout, SettingsLayout] })`.
- Pages de app usam `setLayoutProps({ breadcrumbs: [{ title: 'app...', href: rota() }] })`; hrefs via Wayfinder, nunca URL hardcoded.
- Valores reativos em layoutProps: `watchEffect` ou computed.
- Props compartilhadas em breadcrumbs: `usePage().props.*`.
- Landing pages públicas podem ficar sem layout.
- O layout recebe **chaves** de tradução e as resolve internamente com `t()`.

## 2. Arquitetura de traduções (árvore `app.*`)

**Objetivo:** locale `pt_BR` com fallback `en`; strings da aplicação em arquivos por grupo `lang/pt_BR/app/*.php` (auth.php, nav.php, common.php, settings.php, mail.php, ...) acessíveis como `app.auth.login.title`; frontend consome a mesma árvore sem duplicação.

### Backend — MergingFileLoader

Criar `app/Support/Translation/MergingFileLoader.php` estendendo `Illuminate\Translation\FileLoader`. Ele mescla o diretório homônimo do grupo (`lang/pt_BR/app/`) sobre o arquivo plano (`app.php`): cada `.php` vira uma chave, cada subdiretório um nível aninhado.

```php
class MergingFileLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null)
    {
        $lines = parent::load($locale, $group, $namespace);

        if (($namespace !== null && $namespace !== '*') || $group === '*') {
            return $lines;
        }

        foreach ($this->paths as $path) {
            $directory = "{$path}/{$locale}/{$group}";

            if ($this->files->isDirectory($directory)) {
                $lines = array_replace_recursive($lines, $this->loadDirectory($directory));
            }
        }

        return $lines;
    }

    protected function loadDirectory(string $directory): array
    {
        $lines = [];

        foreach ($this->files->directories($directory) as $subdirectory) {
            $lines[basename($subdirectory)] = $this->loadDirectory($subdirectory);
        }

        foreach ($this->files->files($directory) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = $this->files->getRequire($file->getPathname());

            if (is_array($contents)) {
                $key = $file->getFilenameWithoutExtension();
                $lines[$key] = array_replace_recursive($lines[$key] ?? [], $contents);
            }
        }

        return $lines;
    }
}
```

Registrar no `AppServiceProvider::register()`:

```php
$this->app->extend('translation.loader', function (FileLoader $loader, $app): MergingFileLoader {
    $merging = new MergingFileLoader($app['files'], $loader->paths());

    foreach ($loader->jsonPaths() as $jsonPath) {
        $merging->addJsonPath($jsonPath);
    }

    foreach ($loader->namespaces() as $namespace => $hint) {
        $merging->addNamespace($namespace, $hint);
    }

    return $merging;
});
```

Config: `config/app.php` → `'locale' => 'pt_BR'`, `'fallback_locale' => 'en'`. Strings do framework em `lang/pt_BR/{auth,passwords,pagination,validation}.php` + `pt_BR.json`. E-mails e mensagens de backend sempre via `__('app.grupo.chave')`.

### Compartilhar com o frontend — HandleInertiaRequests

```php
'translations' => fn (): array => [
    'app' => trans('app'),
    'auth' => trans('auth'),
    'passwords' => trans('passwords'),
    'pagination' => trans('pagination'),
    'validation' => trans('validation'),
],
'locale' => app()->getLocale(),
```

### Frontend — composable useT

Criar `resources/js/composables/useT.ts`: lê `page.props.translations`, resolve chave por dot notation, retorna a própria chave quando ausente (torna faltas visíveis na tela) e substitui placeholders `:name` no estilo Laravel:

```ts
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type TranslationTree = { [key: string]: string | TranslationTree };
type Replacements = Record<string, string | number>;

export function useT() {
    const page = usePage();

    const translations = computed<TranslationTree>(
        () => (page.props.translations as TranslationTree | undefined) ?? {},
    );

    const locale = computed<string>(
        () => (page.props.locale as string | undefined) ?? 'pt_BR',
    );

    function t(key: string, replacements?: Replacements): string {
        let node: string | TranslationTree | undefined = translations.value;

        for (const segment of key.split('.')) {
            if (typeof node !== 'object' || node === null) {
                node = undefined;
                break;
            }
            node = node[segment];
        }

        if (typeof node !== 'string') {
            return key;
        }

        if (!replacements) {
            return node;
        }

        return Object.entries(replacements).reduce(
            (message, [name, value]) =>
                message.replaceAll(`:${name}`, String(value)),
            node,
        );
    }

    return { t, locale };
}
```

Regras de uso:
- **Nunca** hardcode texto visível em componentes Vue ou respostas PHP; sempre `t('app.grupo.chave')` / `__('app.grupo.chave')`.
- Chave nova → criar/editar o arquivo do grupo em `lang/pt_BR/app/`.
- **Testes** asserem via `__()`, nunca strings literais em um idioma fixo.

## 3. ULID em vez de autoincrement

Padrão para **toda tabela nova** (aplicar direto, sem perguntar):

```php
// migration
$table->ulid('id')->primary();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->softDeletes();
```

```php
// model
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Product extends Model
{
    use HasFactory, HasUlids, SoftDeletes;
}
```

- Type hints de ids: `string`/`?string`, **nunca** `int` (controllers, form requests, validação de unique, rotas).
- Status: `string(20)` com cast para enum PHP.

**Tabelas existentes com `$table->id()` (autoincrement):**

1. **Pare e pergunte ao usuário** (AskUserQuestion) se deve converter para ULID — nunca converta por iniciativa própria: a conversão afeta FKs, dados existentes e assinaturas públicas.
2. Se autorizado: migration convertendo PK + todas as FKs (`foreignUlid`), migração de dados se houver registros, `HasUlids` no model, varredura `int` → `string` nos type hints, testes verdes.
3. Se negado: mantenha, mas alinhe o tipo de qualquer FK nova apontando para ela.

## 4. Verificação (sempre)

- Frontend: `npx vue-tsc --noEmit` → lint/format do projeto → `npm run build`.
- PHP: `vendor/bin/pint --dirty --format agent` → `php artisan test --compact --filter=<área>`.
- Projetos sem testes de browser: typecheck + build são a verificação de frontend.

## Ao implantar em projeto novo

Se o projeto tiver Laravel Boost/`.ai/rules`, registre estas convenções via `record-rule` (globs: `database/migrations/**`, `app/Models/**`, `lang/**,resources/js/**`) para que outros agentes as herdem. Considere também copiar esta skill para `.claude/skills/` do repo e adaptá-la (nomes de layouts, grupos de tradução) para o time inteiro usá-la.
