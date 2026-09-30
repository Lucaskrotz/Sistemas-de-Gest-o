<?php

namespace App\Services\Financeiro;

use App\Models\Titulo;
use App\Models\WebhookEvento;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Processa um webhook do banco: assinatura → formato → idempotência → baixa do título.
 * Todo evento recebido é registrado em webhook_eventos, inclusive os rejeitados.
 */
class WebhookProcessor
{
    public function processar(string $corpo, ?string $assinatura): WebhookEvento
    {
        $payload = json_decode($corpo, true);
        $evento = new WebhookEvento([
            'evento_id' => is_array($payload) ? ($payload['id'] ?? null) : null,
            'tipo' => is_array($payload) ? ($payload['tipo'] ?? 'desconhecido') : 'desconhecido',
            'payload' => $corpo,
            'assinatura' => $assinatura,
            'assinatura_valida' => $assinatura !== null && hash_equals(BancoFake::assinar($corpo), $assinatura),
        ]);

        if (! $evento->assinatura_valida) {
            return $this->registrar($evento, 'rejeitado', 'Assinatura HMAC inválida.');
        }
        $cobranca = $payload['dados']['cobranca_id'] ?? null;
        if (! $evento->evento_id || ! $cobranca) {
            return $this->registrar($evento, 'erro', 'Payload malformado.');
        }
        if (WebhookEvento::where('evento_id', $evento->evento_id)->where('status', 'processado')->exists()) {
            return $this->registrar($evento, 'ignorado', 'Evento já processado (idempotência).');
        }

        return DB::transaction(function () use ($evento, $payload, $cobranca) {
            $titulo = Titulo::where('codigo_cobranca', $cobranca)->lockForUpdate()->first();
            if (! $titulo) {
                return $this->registrar($evento, 'erro', "Cobrança {$cobranca} não encontrada.");
            }
            $evento->titulo_id = $titulo->id;

            if ($evento->tipo !== 'cobranca.paga') {
                return $this->registrar($evento, 'ignorado', "Tipo de evento {$evento->tipo} não tratado.");
            }
            if ($titulo->status !== 'aberto') {
                return $this->registrar($evento, 'ignorado', "Título já está {$titulo->status}.");
            }

            $valor = (float) ($payload['dados']['valor_pago'] ?? $titulo->valor);
            $titulo->update([
                'status' => 'pago',
                'valor_pago' => $valor,
                'pago_em' => Carbon::parse($payload['dados']['pago_em'] ?? now()),
            ]);

            return $this->registrar($evento, 'processado', 'Baixa de R$ '.number_format($valor, 2, ',', '.').' realizada.');
        });
    }

    private function registrar(WebhookEvento $evento, string $status, string $mensagem): WebhookEvento
    {
        $evento->fill(compact('status', 'mensagem'))->save();

        return $evento;
    }
}
