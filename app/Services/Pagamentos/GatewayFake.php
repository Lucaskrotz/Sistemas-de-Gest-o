<?php

namespace App\Services\Pagamentos;

use App\Models\Pagamento;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gateway de pagamento fictício (estilo Stripe/Asaas). Máquina de estados:
 *
 *   pix:    pendente ─RECEIVED→ recebido ─REFUNDED→ estornado
 *           pendente ─OVERDUE→  expirado
 *   cartão: (criado) ─CONFIRMED→ confirmado ─RECEIVED→ recebido ─REFUNDED→ estornado
 *           (criado) ─REFUSED→   recusado        └──────REFUNDED→ estornado
 *
 * Cada transição grava um evento PAYMENT_*. Nenhum dado de cartão além de bandeira e final é persistido.
 */
class GatewayFake
{
    public const TAXAS = ['pix' => 0.0099, 'cartao' => 0.0399];

    public const PIX_VALIDADE_MIN = 30;

    /** Cartões de teste (como no Stripe): qualquer outro número válido é aprovado. */
    public const CARTOES_TESTE = [
        '4242424242424242' => null,
        '4000000000000002' => 'Cartão recusado pelo emissor.',
        '4000000000009995' => 'Saldo insuficiente.',
    ];

    /** Cria a cobrança. Mesma idempotency key → devolve a cobrança já existente (sem cobrar de novo). */
    public function criar(array $dados, string $chave): Pagamento
    {
        try {
            return $this->criarNovo($dados, $chave);
        } catch (UniqueConstraintViolationException) {
            // Duas requisições simultâneas com a mesma chave: a segunda recebe a cobrança da primeira.
            return Pagamento::where('idempotency_key', $chave)->firstOrFail();
        }
    }

    private function criarNovo(array $dados, string $chave): Pagamento
    {
        return DB::transaction(function () use ($dados, $chave) {
            if ($existente = Pagamento::where('idempotency_key', $chave)->first()) {
                return $existente;
            }

            $taxa = round($dados['valor'] * self::TAXAS[$dados['metodo']], 2);
            $pagamento = Pagamento::create([
                'codigo' => 'pay_'.Str::lower(Str::random(14)),
                'idempotency_key' => $chave,
                'cliente_id' => $dados['cliente_id'],
                'descricao' => $dados['descricao'],
                'valor' => $dados['valor'],
                'taxa' => $taxa,
                'valor_liquido' => $dados['valor'] - $taxa,
                'metodo' => $dados['metodo'],
                'status' => 'pendente',
                'bandeira' => isset($dados['numero']) ? self::bandeira($dados['numero']) : null,
                'cartao_final' => isset($dados['numero']) ? substr($dados['numero'], -4) : null,
                'parcelas' => $dados['parcelas'] ?? 1,
                'pix_copia_cola' => $dados['metodo'] === 'pix' ? $this->pixCopiaCola($dados['valor']) : null,
                'expira_em' => $dados['metodo'] === 'pix' ? now()->addMinutes(self::PIX_VALIDADE_MIN) : null,
            ]);
            $this->evento($pagamento, 'PAYMENT_CREATED');

            if ($dados['metodo'] === 'cartao') {
                $motivo = self::CARTOES_TESTE[$dados['numero']] ?? null;
                $motivo
                    ? $this->transicionar($pagamento, ['pendente'], 'recusado', 'PAYMENT_REFUSED', ['motivo' => $motivo])
                    : $this->transicionar($pagamento, ['pendente'], 'confirmado', 'PAYMENT_CONFIRMED', ['autorizacao' => (string) random_int(100000, 999999)]);
            }

            return $pagamento->refresh();
        });
    }

    public function receberPix(Pagamento $p): void
    {
        $this->exigirMetodo($p, 'pix');
        $this->transicionar($p, ['pendente'], 'recebido', 'PAYMENT_RECEIVED', ['end_to_end_id' => 'E'.strtoupper(Str::random(31))]);
    }

    /** Liquidação do cartão (no mundo real, D+30; aqui, sob demanda). */
    public function liquidar(Pagamento $p): void
    {
        $this->exigirMetodo($p, 'cartao');
        $this->transicionar($p, ['confirmado'], 'recebido', 'PAYMENT_RECEIVED', ['valor_liquido' => (float) $p->valor_liquido]);
    }

    public function expirar(Pagamento $p): void
    {
        $this->exigirMetodo($p, 'pix');
        $this->transicionar($p, ['pendente'], 'expirado', 'PAYMENT_OVERDUE');
    }

    public function estornar(Pagamento $p): void
    {
        $this->transicionar($p, ['confirmado', 'recebido'], 'estornado', 'PAYMENT_REFUNDED', ['valor_estornado' => (float) $p->valor]);
    }

    public static function luhn(string $numero): bool
    {
        $soma = 0;
        foreach (array_reverse(str_split($numero)) as $i => $d) {
            $d = (int) $d * ($i % 2 ? 2 : 1);
            $soma += $d > 9 ? $d - 9 : $d;
        }

        return $soma % 10 === 0;
    }

    public static function bandeira(string $numero): string
    {
        return match (true) {
            str_starts_with($numero, '4') => 'Visa',
            (bool) preg_match('/^(5[1-5]|2[2-7])/', $numero) => 'Mastercard',
            (bool) preg_match('/^3[47]/', $numero) => 'Amex',
            default => 'Cartão',
        };
    }

    private function transicionar(Pagamento $p, array $de, string $para, string $evento, array $extra = []): void
    {
        DB::transaction(function () use ($p, $de, $para, $evento, $extra) {
            $atual = Pagamento::whereKey($p->id)->lockForUpdate()->value('status');
            if (! in_array($atual, $de, true)) {
                throw new DomainException('Transição inválida: pagamento está "'.Pagamento::STATUS[$atual][0].'".');
            }
            $p->update(['status' => $para]);
            $this->evento($p, $evento, $extra);
        });
    }

    private function exigirMetodo(Pagamento $p, string $metodo): void
    {
        if ($p->metodo !== $metodo) {
            throw new DomainException('Ação disponível só para '.Pagamento::METODOS[$metodo].'.');
        }
    }

    private function evento(Pagamento $p, string $tipo, array $extra = []): void
    {
        $p->eventos()->create(['tipo' => $tipo, 'dados' => [
            'event' => $tipo,
            'payment' => ['id' => $p->codigo, 'status' => $p->status, 'value' => (float) $p->valor,
                'net_value' => (float) $p->valor_liquido, 'billing_type' => strtoupper($p->metodo)],
        ] + $extra]);
    }

    private function pixCopiaCola(float $valor): string
    {
        // Formato EMV simplificado e fictício (chave inexistente).
        return '00020126580014BR.GOV.BCB.PIX0136'.Str::uuid().'5204000053039865406'.number_format($valor, 2, '.', '')
            .'5802BR5913PORTFOLIO DEMO6009SAO PAULO62070503***6304'.strtoupper(Str::random(4));
    }
}
