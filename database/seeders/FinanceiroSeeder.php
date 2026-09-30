<?php

namespace Database\Seeders;

use App\Models\Pedido;
use App\Models\Titulo;
use App\Models\WebhookEvento;
use App\Services\Financeiro\BancoFake;
use Illuminate\Database\Seeder;

class FinanceiroSeeder extends Seeder
{
    public function run(BancoFake $banco): void
    {
        $pedidos = Pedido::where('status', '!=', 'cancelado')->orderBy('created_at')->get();

        foreach ($pedidos as $pedido) {
            $vencimento = $pedido->created_at->copy()->addDays(30)->startOfDay();
            $titulo = new Titulo([
                'pedido_id' => $pedido->id,
                'cliente_id' => $pedido->cliente_id,
                'descricao' => "Pedido {$pedido->numero}",
                'valor' => $pedido->total,
                'vencimento' => $vencimento,
            ]);

            // Antigos quase sempre pagos; a inadimplência fica nos vencimentos dos últimos 45 dias.
            $pago = $vencimento->lt(today()->subDays(3)) && fake()->boolean($vencimento->lt(today()->subDays(45)) ? 98 : 70);
            if ($pago || fake()->boolean(65)) {
                $cobranca = $banco->registrarCobranca($titulo);
                $titulo->fill(['codigo_cobranca' => $cobranca['id'], 'linha_digitavel' => $cobranca['linha_digitavel']]);
            }
            if ($pago) {
                // maioria paga até o vencimento; algumas com atraso
                $pagoEm = $vencimento->copy()->addDays(fake()->numberBetween(-10, fake()->boolean(80) ? 0 : 6))->setTime(fake()->numberBetween(8, 20), fake()->numberBetween(0, 59));
                $titulo->fill(['status' => 'pago', 'pago_em' => $pagoEm->min(now()), 'valor_pago' => $titulo->valor]);
            }
            $titulo->save();

            if ($pago && $titulo->pago_em->gt(now()->subDays(20))) {
                [$corpo, $assinatura] = $banco->webhookPagamento($titulo, pagoEm: $titulo->pago_em->toIso8601String());
                $this->evento($titulo, $corpo, $assinatura, 'processado', 'Baixa de R$ '.number_format($titulo->valor, 2, ',', '.').' realizada.', $titulo->pago_em);
            }
        }

        // Exemplos de rejeição e de idempotência no log.
        $comCobranca = Titulo::where('status', 'aberto')->whereNotNull('codigo_cobranca')->first();
        if ($comCobranca) {
            [$corpo, $assinatura] = $banco->webhookPagamento($comCobranca, assinaturaValida: false);
            $this->evento($comCobranca, $corpo, $assinatura, 'rejeitado', 'Assinatura HMAC inválida.', now()->subHours(5), false);
        }
        $ultimo = WebhookEvento::where('status', 'processado')->latest('created_at')->first();
        if ($ultimo) {
            $this->evento($ultimo->titulo, $ultimo->payload, $ultimo->assinatura, 'ignorado', 'Evento já processado (idempotência).', $ultimo->created_at->copy()->addMinutes(2));
        }
    }

    private function evento(Titulo $titulo, string $corpo, string $assinatura, string $status, string $mensagem, $quando, bool $valida = true): void
    {
        WebhookEvento::create([
            'titulo_id' => $titulo->id,
            'evento_id' => json_decode($corpo, true)['id'],
            'tipo' => 'cobranca.paga',
            'payload' => $corpo,
            'assinatura' => $assinatura,
            'assinatura_valida' => $valida,
            'status' => $status,
            'mensagem' => $mensagem,
            'created_at' => $quando,
        ]);
    }
}
