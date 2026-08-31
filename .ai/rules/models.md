---
paths:
  - 'database/migrations/**,app/Models/**'
---

# Models

## ULID como chave primária (nunca autoincrement)
Toda tabela nova: `$table->ulid('id')->primary()`, FKs com `$table->foreignUlid(...)->constrained()->cascadeOnDelete()` e `$table->softDeletes()`. Models usam o trait `HasUlids` (+ `SoftDeletes` quando aplicável). Type hints de ids são `string`/`?string`, nunca `int` (controllers, form requests, rotas, validação unique). Colunas de status: `string(20)` com cast para enum PHP. users e passkeys já foram convertidas; o model Passkey da app (App\Models\Passkey) estende o do pacote laravel/passkeys com HasUlids e é registrado via Passkeys::usePasskeyModel() no AppServiceProvider.
