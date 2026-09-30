# Portfólio Demos — 6 sistemas em 1 app Laravel

Demos de portfólio, NÃO sistemas completos. Escopo pequeno, visual profissional,
dados fictícios. Cada módulo = um "projeto" com rota própria, linkado do portfólio.

## Stack
- PHP 8.3, Laravel 13, MySQL 8.4
- Docker: `app` (php-fpm alpine), `nginx` (porta 8000), `mysql` (porta 3306)
- Frontend: Blade + Bootstrap 5 + DataTables + Chart.js **via CDN** — sem Vite/npm
  (Node do WSL é 18, velho demais para o Vite atual; não reintroduzir build)
- Visual: design system **shadcn/ui** (tema Neutral) recriado sobre Bootstrap em `public/css/app.css`
  (tokens `--background`, `--muted`, `--border`…; classes extras `btn-ghost`, `btn-icone`, `tabs`,
  `card-title`/`card-description`). Fonte Geist, ícones Lucide (CDN). Não usar Tailwind/React.
- Gráficos: paleta categórica validada (`#2a78d6`, `#eb6834`…), 1 eixo, tooltip + "Ver tabela"
- JS puro (fetch/AJAX); Kanban com drag-and-drop nativo HTML5
- Testes: PHPUnit (`php artisan test`)

## Módulos (prefixo de rota → pasta)
| Módulo | Rota | Competência demonstrada |
|---|---|---|
| Erp | /erp | CRUD, banco, DataTables |
| Financeiro | /financeiro | API simulada + webhook → baixa de títulos |
| Tenancy | /multitenant | isolamento por tenant_id |
| Indicadores | /indicadores | Chart.js + AJAX + filtros de período |
| Pagamentos | /pagamentos | gateway fake + eventos PAYMENT_* |
| Chamados | /chamados | Kanban, SLA, histórico |

Ordem de construção: base → Erp → Chamados → Indicadores → Financeiro → Pagamentos → Tenancy.

## Estrutura
- Controllers: `app/Http/Controllers/{Modulo}/`
- Models: `app/Models/` (compartilhados: Cliente, Produto, Pedido, User)
- Services: `app/Services/{Modulo}/` — só onde há lógica real (gateway fake, webhook)
- Middleware: `app/Http/Middleware/` (registrado em `bootstrap/app.php`)
- Rotas: `routes/{modulo}.php`, registradas em `bootstrap/app.php`
- Views: `resources/views/{modulo}/`; layout base `resources/views/layouts/app.blade.php`
- Components Blade: `resources/views/components/` (card, badge, modal, stat…)
- Migrations/Seeders: `database/migrations`, `database/seeders/{Modulo}Seeder.php`
- Docker: `docker-compose.yml`, `docker/php/Dockerfile`, `docker/nginx/default.conf`

## Padrões
- Layout único; cada módulo passa menu da sidebar, nome e cor de destaque.
- Dados do ERP (clientes/produtos/pedidos) alimentam Financeiro, Indicadores e Chamados.
- Sem repository pattern, sem interfaces de 1 implementação. Controller → Model;
  Service só para fluxo com múltiplos passos.
- Nomes de domínio em português (Cliente, Pedido, Chamado); resto no padrão Laravel.
- Validação via `$request->validate()`; FormRequest só quando reutilizada.
- Feedback: flash `success`/`error` renderizado no layout.
- Seeders com Faker pt_BR (`APP_FAKER_LOCALE=pt_BR`).

## Multi-tenant (módulo Tenancy)
- Banco único, coluna `tenant_id` nas tabelas do módulo.
- Tenant = empresa do usuário logado; middleware define o tenant atual.
- Trait `BelongsToTenant` com global scope + preenchimento automático de tenant_id.
- Demo: alternar Empresa A/B mostra usuários/dados isolados.
- NUNCA usar `withoutGlobalScopes()` fora de código de admin/seeder.
- Regras `exists`/`unique` e `DB::table` NÃO passam pelo global scope: sempre `->where('tenant_id', Tenant::atual()->id)`.
- `IdentificarTenant` tem prioridade sobre `SubstituteBindings` (bootstrap/app.php) — não remover, senão
  `{id}` de outra empresa é resolvido. Seeders/testes: `Tenant::comoTenant($t, fn () => ...)`.

## Integrações
- Tudo SIMULADO dentro do app: gateway fake, API fake, webhooks internos.
- Nenhum gateway, banco ou API real. Nenhuma chave real, em lugar nenhum.

## Demo / acesso
- `/demo/{modulo}` loga automaticamente o usuário demo do módulo.
- Banco resetado diariamente: `migrate:fresh --seed` agendado.

## Comandos
Rodar no WSL, na raiz do projeto. PHP/artisan/composer SEMPRE dentro do container
(o PHP local não tem pdo_mysql):
```bash
docker compose up -d                       # sobe app + nginx + mysql → http://localhost:8000
docker compose down
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed    # reset com dados fictícios
docker compose exec app php artisan db:seed --class=ErpSeeder
docker compose exec app php artisan route:list --path=erp
docker compose exec app php artisan optimize:clear          # config/route/view/cache
docker compose exec app php artisan test
docker compose exec app php artisan test --filter=NomeDoTeste
docker compose exec app php artisan tinker
docker compose exec app composer install
docker compose exec mysql mysql -u"$DB_USERNAME" -p portfolio   # console SQL
```

## Segurança
- Segredos só em `.env` (não versionado; leitura bloqueada em `.claude/settings.json`).
  Novas variáveis: documentar em `.env.example` com valor fictício.
- Nunca escrever senha, token ou chave real em código, docs ou commits.

## Regras de contexto (economia de tokens)
1. Ler este arquivo; não explorar o projeto inteiro.
2. Ir direto à pasta do módulo da tarefa; buscas com Grep direcionado.
3. Não ler `vendor/` salvo para entender comportamento do framework.
4. Logs: `tail -n 50 storage/logs/laravel.log` ou `grep` pela mensagem.
   Extrair mensagem, arquivo:linha e só os frames do app (`app/`, `routes/`).
   Nunca carregar o log inteiro.
5. Não reler arquivos já lidos na sessão; não ler duplicatas.
6. Alterar só os arquivos necessários; validar só o que mudou
   (`php artisan test --filter`, `route:list --path`).
7. Perguntas paralelas (sintaxe, conceito, erro avulso) → `/btw`, não na tarefa.

## Nível de raciocínio
- Padrão (sem palavra-chave): CRUD, Blade, controllers, models, migrations,
  filtros, queries, frontend, bugs simples.
- `ultrathink`: só arquitetura, segurança complexa, concorrência, bug
  irreproduzível, refatoração ampla, fluxo entre vários sistemas.
  Não usar a palavra em prompts reutilizados (ativa sempre).

## Antes de alterar
- Confirmar o módulo e os arquivos afetados.
- Não mudar layout base / components compartilhados sem avisar (afeta os 6 módulos).
- Não adicionar dependência (composer/CDN) sem autorização.
