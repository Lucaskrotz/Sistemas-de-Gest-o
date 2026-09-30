<?php

namespace App\Services\Financeiro;

use App\Models\Titulo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * "API" de um banco fictício: registra cobranças e emite webhooks assinados (HMAC-SHA256).
 * Tudo local; nenhum banco real é chamado. Código de banco 999 = inexistente.
 */
class BancoFake
{
    public function registrarCobranca(Titulo $titulo): array
    {
        $digitos = fn (int $n) => collect(range(1, $n))->map(fn () => random_int(0, 9))->join('');

        return [
            'id' => 'COB-'.strtoupper(Str::random(10)),
            'status' => 'registrada',
            'linha_digitavel' => sprintf('99990.%s %s.%s %s.%s %d %s',
                $digitos(5), $digitos(5), $digitos(6), $digitos(5), $digitos(6), random_int(1, 9),
                str_pad((int) round($titulo->valor * 100), 14, '0', STR_PAD_LEFT)),
        ];
    }

    /** @return array{0: string, 1: string} corpo JSON e cabeçalho de assinatura */
    public function webhookPagamento(Titulo $titulo, bool $assinaturaValida = true, ?string $pagoEm = null): array
    {
        $corpo = json_encode([
            'id' => 'evt_'.Str::lower(Str::ulid()),
            'tipo' => 'cobranca.paga',
            'criado_em' => now()->toIso8601String(),
            'dados' => [
                'cobranca_id' => $titulo->codigo_cobranca,
                'valor_pago' => (float) $titulo->valor,
                'pago_em' => $pagoEm ?? now()->toIso8601String(),
                'meio' => Arr::random(['pix', 'boleto']),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return [$corpo, $assinaturaValida ? self::assinar($corpo) : 'sha256='.str_repeat('0', 64)];
    }

    public static function assinar(string $corpo): string
    {
        return 'sha256='.hash_hmac('sha256', $corpo, config('services.banco_fake.webhook_secret'));
    }
}
