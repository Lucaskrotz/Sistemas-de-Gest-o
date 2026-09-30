<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Services\Pagamentos\GatewayFake;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class PagamentosSeeder extends Seeder
{
    private const DESCRICOES = ['Assinatura Plano Pro (mensal)', 'Licença anual ERP', 'Implantação e treinamento',
        'Suporte premium', 'Módulo fiscal', 'Consultoria (4h)', 'Assinatura Plano Básico (mensal)', 'Upgrade de usuários'];

    public function run(GatewayFake $gateway): void
    {
        $clientes = Cliente::where('ativo', true)->pluck('id');

        // Passa pelo próprio gateway com o relógio "voltado" — eventos e datas coerentes com a máquina de estados.
        $agora = Carbon::now();
        $em = fn (Carbon $quando) => Carbon::setTestNow($quando->min($agora));

        foreach (range(1, 70) as $_) {
            // 15% nas últimas 36h: garante Pix aguardando e cartões ainda não liquidados na demo.
            $criadoEm = $agora->copy()->subMinutes(fake()->numberBetween(20, fake()->boolean(15) ? 60 * 36 : 60 * 24 * 60));
            Carbon::setTestNow($criadoEm);

            $cartao = fake()->boolean(62);
            $pagamento = $gateway->criar([
                'cliente_id' => $clientes->random(),
                'descricao' => fake()->randomElement(self::DESCRICOES),
                'valor' => fake()->randomElement([49.9, 99.9, 149.9, 299, 490, 890, 1290, 2400, 4800]),
                'metodo' => $cartao ? 'cartao' : 'pix',
                'numero' => $cartao ? fake()->randomElement(array_merge(array_fill(0, 16, '4242424242424242'),
                    ['5555555555554444', '5555555555554444', '4000000000000002', '4000000000009995'])) : null,
                'parcelas' => $cartao ? fake()->randomElement([1, 1, 1, 2, 3, 6, 12]) : 1,
            ], (string) Str::uuid());

            // Pix com mais de 3h: pago em minutos (85%) ou expirado; os recentes ficam aguardando.
            if ($pagamento->metodo === 'pix' && $criadoEm->lt($agora->copy()->subHours(3))) {
                $em($criadoEm->copy()->addMinutes(fake()->numberBetween(1, 25)));
                fake()->boolean(85) ? $gateway->receberPix($pagamento) : $gateway->expirar($pagamento);
            }
            // Cartão confirmado há mais de 2 dias: liquidado.
            if ($pagamento->status === 'confirmado' && $criadoEm->lt($agora->copy()->subDays(2))) {
                $em($criadoEm->copy()->addDays(2)->setTime(3, 0));
                $gateway->liquidar($pagamento);
            }
            if (in_array($pagamento->status, ['confirmado', 'recebido']) && fake()->boolean(6)) {
                $em($criadoEm->copy()->addDays(3));
                $gateway->estornar($pagamento);
            }
        }

        // Alguns Pix gerados agora há pouco, ainda dentro da validade (aguardando pagamento).
        foreach (range(1, 3) as $_) {
            $em($agora->copy()->subMinutes(fake()->numberBetween(2, 20)));
            $gateway->criar([
                'cliente_id' => $clientes->random(),
                'descricao' => fake()->randomElement(self::DESCRICOES),
                'valor' => fake()->randomElement([99.9, 299, 890]),
                'metodo' => 'pix',
            ], (string) Str::uuid());
        }

        Carbon::setTestNow();
    }
}
