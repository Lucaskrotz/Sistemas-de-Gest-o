<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Titulo;
use App\Models\User;
use App\Models\WebhookEvento;
use App\Services\Financeiro\BancoFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceiroTest extends TestCase
{
    use RefreshDatabase;

    private function titulo(array $attrs = []): Titulo
    {
        return Titulo::create($attrs + [
            'cliente_id' => Cliente::factory()->create()->id,
            'descricao' => 'Teste',
            'valor' => 150.50,
            'vencimento' => today()->addDays(10),
            'codigo_cobranca' => 'COB-TESTE123',
        ]);
    }

    /** Envia ao endpoint público como o banco faria (corpo bruto + cabeçalho). */
    private function webhook(string $corpo, ?string $assinatura)
    {
        return $this->call('POST', route('financeiro.webhook'), server: array_filter([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ASSINATURA' => $assinatura,
        ]), content: $corpo);
    }

    public function test_webhook_valido_da_baixa_no_titulo(): void
    {
        $titulo = $this->titulo();
        [$corpo, $assinatura] = app(BancoFake::class)->webhookPagamento($titulo);

        $this->webhook($corpo, $assinatura)->assertOk()->assertJson(['status' => 'processado']);

        $titulo->refresh();
        $this->assertSame('pago', $titulo->status);
        $this->assertEquals(150.50, $titulo->valor_pago);
        $this->assertNotNull($titulo->pago_em);
        $this->assertDatabaseHas('webhook_eventos', ['titulo_id' => $titulo->id, 'status' => 'processado', 'assinatura_valida' => true]);
    }

    public function test_assinatura_invalida_ou_ausente_e_rejeitada_sem_baixa(): void
    {
        $titulo = $this->titulo();
        [$corpo] = app(BancoFake::class)->webhookPagamento($titulo);

        $this->webhook($corpo, 'sha256='.str_repeat('0', 64))->assertUnauthorized()->assertJson(['status' => 'rejeitado']);
        $this->webhook($corpo, null)->assertUnauthorized();
        $this->webhook(str_replace('150.5', '1.0', $corpo), BancoFake::assinar($corpo))->assertUnauthorized(); // corpo adulterado

        $this->assertSame('aberto', $titulo->fresh()->status);
        $this->assertSame(3, WebhookEvento::where('status', 'rejeitado')->count());
    }

    public function test_reenvio_do_mesmo_evento_e_idempotente(): void
    {
        $titulo = $this->titulo();
        [$corpo, $assinatura] = app(BancoFake::class)->webhookPagamento($titulo);

        $this->webhook($corpo, $assinatura)->assertOk()->assertJson(['status' => 'processado']);
        $pagoEm = $titulo->fresh()->pago_em;
        $this->travel(1)->hour();
        $this->webhook($corpo, $assinatura)->assertOk()->assertJson(['status' => 'ignorado']);

        $this->assertTrue($titulo->fresh()->pago_em->eq($pagoEm));
        $this->assertSame(1, WebhookEvento::where('status', 'processado')->count());
    }

    public function test_cobranca_inexistente_e_payload_malformado(): void
    {
        $this->webhook($c = '{"id":"evt_x","tipo":"cobranca.paga","dados":{"cobranca_id":"COB-NAOEXISTE"}}', BancoFake::assinar($c))
            ->assertUnprocessable()->assertJson(['status' => 'erro']);
        $this->webhook($c = 'isso nao e json', BancoFake::assinar($c))->assertUnprocessable();
    }

    public function test_importar_gera_titulos_so_para_pedidos_sem_titulo_e_nao_cancelados(): void
    {
        $this->actingAs(User::factory()->create());
        $cliente = Cliente::factory()->create();
        $ok = Pedido::forceCreate(['cliente_id' => $cliente->id, 'status' => 'pago', 'total' => 300, 'created_at' => '2026-09-01 10:00']);
        Pedido::forceCreate(['cliente_id' => $cliente->id, 'status' => 'cancelado', 'total' => 99]);

        $this->post(route('financeiro.importar'))->assertSessionHas('success');
        $this->post(route('financeiro.importar'))->assertSessionHas('success', 'Nenhum pedido novo para importar.');

        $titulo = Titulo::sole();
        $this->assertSame($ok->id, $titulo->pedido_id);
        $this->assertSame('2026-10-01', $titulo->vencimento->toDateString());
    }

    public function test_fluxo_pela_tela_registrar_cobranca_e_simular_pagamento(): void
    {
        $this->actingAs(User::factory()->create());
        $titulo = $this->titulo(['codigo_cobranca' => null]);

        $this->post(route('financeiro.titulos.cobranca', $titulo))->assertSessionHas('success');
        $this->assertStringStartsWith('COB-', $titulo->fresh()->codigo_cobranca);

        $this->post(route('financeiro.titulos.simular', $titulo), ['invalida' => 1])->assertSessionHas('error');
        $this->assertSame('aberto', $titulo->fresh()->status);

        $this->post(route('financeiro.titulos.simular', $titulo))->assertSessionHas('success');
        $this->assertSame('pago', $titulo->fresh()->status);

        foreach (['financeiro.index', 'financeiro.webhooks'] as $rota) {
            $this->get(route($rota))->assertOk();
        }
        $this->get(route('financeiro.titulos.show', $titulo))->assertOk()->assertSee('Baixa de R$ 150,50');
    }

    public function test_titulo_aberto_com_vencimento_passado_fica_vencido(): void
    {
        $this->assertSame('vencido', $this->titulo(['vencimento' => today()->subDay()])->situacao);
        $this->assertSame('aberto', $this->titulo(['vencimento' => today(), 'codigo_cobranca' => null])->situacao);
    }
}
