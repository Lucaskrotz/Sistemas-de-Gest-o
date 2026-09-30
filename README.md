# Portfólio · 6 sistemas de gestão em Laravel

Seis demos independentes num único app Laravel, cada uma com rota própria, dados fictícios e login automático.
Todas as integrações (banco, gateway, webhooks) são **simuladas dentro do app** — nenhuma chave ou serviço real.

| Demo | Rota | O que demonstra |
|---|---|---|
| **Gestor ERP** | `/erp` | CRUD de clientes, produtos e pedidos; DataTables; controle de estoque em transação com lock |
| **Help Desk** | `/chamados` | Kanban com drag-and-drop nativo (HTML5 + fetch), SLA por prioridade, histórico de atendimento |
| **Indicadores** | `/indicadores` | Dashboard com Chart.js via AJAX, filtros de período, comparação com período anterior |
| **Financeiro** | `/financeiro` | Títulos a receber gerados do ERP; webhook público com HMAC-SHA256, idempotência e baixa automática |
| **Pagamentos** | `/pagamentos` | Gateway fake (Pix/cartão), máquina de estados com eventos `PAYMENT_*`, Luhn, idempotency key |
| **Multi-tenant** | `/multitenant` | Isolamento por `tenant_id` com global scope *fail-closed*, middleware de tenant, troca de empresa |

Acesse `/demo/{modulo}` (ex.: `/demo/erp`) para entrar já logado com o usuário demo. O banco é recriado diariamente.

## Stack

- PHP 8.3 · Laravel 13 · MySQL 8.4 · Docker (php-fpm + nginx + mysql)
- Blade + Bootstrap 5 com o design system do **shadcn/ui** recriado em CSS (`public/css/app.css`)
- DataTables, Chart.js e ícones Lucide via CDN — sem build de front-end
- JavaScript puro (fetch/AJAX)
- PHPUnit: testes de feature por módulo

## Destaques técnicos

- **Webhook seguro** (`app/Services/Financeiro/WebhookProcessor.php`): valida assinatura HMAC do corpo bruto com `hash_equals`, trata reenvio pelo `id` do evento, trava o título com `lockForUpdate` e registra todo evento recebido (inclusive rejeitados).
- **Máquina de estados de pagamento** (`app/Services/Pagamentos/GatewayFake.php`): transições explícitas, cada uma gera um evento `PAYMENT_*`; número do cartão nunca é persistido (só bandeira e final) nem vai para a sessão.
- **Multi-tenancy** (`app/Models/Concerns/BelongsToTenant.php`): global scope que não retorna nada sem tenant definido, `tenant_id` preenchido e imutável, middleware com prioridade sobre o route model binding e regras de validação filtradas por tenant.
- **Estoque consistente** (`app/Http/Controllers/Erp/PedidoController.php`): criação de pedido em transação com lock nas linhas dos produtos.

## Rodando localmente

```bash
cp .env.example .env
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
# http://localhost:8000
```

Testes:

```bash
docker compose exec app php artisan test
```
