<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pagamento;
use App\Models\User;
use App\Services\Pagamentos\GatewayFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PagamentosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function cobrar(array $extra = [])
    {
        return $this->post(route('pagamentos.store'), $extra + [
            'idempotency_key' => (string) Str::uuid(),
            'cliente_id' => Cliente::factory()->create()->id,
            'descricao' => 'Plano Pro',
            'valor' => '100.00',
            'metodo' => 'cartao',
            'numero' => '4242 4242 4242 4242',
            'nome' => 'CLIENTE DEMO',
            'validade' => '12/'.now()->addYears(2)->format('y'),
            'cvv' => '123',
            'parcelas' => 3,
        ]);
    }

    private function tipos(Pagamento $p): array
    {
        return $p->eventos()->pluck('tipo')->all();
    }

    public function test_cartao_aprovado_guarda_so_final_e_calcula_taxa(): void
    {
        $this->cobrar()->assertSessionHas('success', 'Pagamento aprovado.');

        $p = Pagamento::sole();
        $this->assertSame('confirmado', $p->status);
        $this->assertSame(['PAYMENT_CREATED', 'PAYMENT_CONFIRMED'], $this->tipos($p));
        $this->assertSame(['Visa', '4242', 3], [$p->bandeira, $p->cartao_final, $p->parcelas]);
        $this->assertEquals(3.99, $p->taxa);
        $this->assertEquals(96.01, $p->valor_liquido);
        $this->assertStringNotContainsString('4242424242424242', json_encode([$p->toArray(), $p->eventos->toArray()]));
    }

    public function test_cartoes_de_teste_recusados(): void
    {
        $this->cobrar(['numero' => '4000000000000002'])->assertSessionHas('error', 'Pagamento recusado: Cartão recusado pelo emissor.');
        $p = Pagamento::sole();
        $this->assertSame('recusado', $p->status);
        $this->assertSame(['PAYMENT_CREATED', 'PAYMENT_REFUSED'], $this->tipos($p));
    }

    public function test_validacao_do_cartao_e_numero_nao_vai_para_a_sessao(): void
    {
        $this->cobrar(['numero' => '4242424242424241'])->assertSessionHasErrors('numero')->assertSessionMissing('_old_input.numero');
        $this->cobrar(['validade' => '01/20'])->assertSessionHasErrors('validade');
        $this->cobrar(['cvv' => '12'])->assertSessionHasErrors('cvv');
        $this->assertDatabaseCount('pagamentos', 0);
    }

    public function test_mesma_idempotency_key_nao_cobra_duas_vezes(): void
    {
        $chave = (string) Str::uuid();
        $cliente = Cliente::factory()->create()->id;

        $this->cobrar(['idempotency_key' => $chave, 'cliente_id' => $cliente]);
        $this->cobrar(['idempotency_key' => $chave, 'cliente_id' => $cliente, 'valor' => '999']);

        $this->assertSame(1, Pagamento::count());
        $this->assertEquals(100, Pagamento::sole()->valor);
    }

    public function test_fluxo_pix_recebido_e_estornado(): void
    {
        $this->cobrar(['metodo' => 'pix', 'numero' => null]);
        $p = Pagamento::sole();
        $this->assertSame('pendente', $p->status);
        $this->assertNull($p->cartao_final);
        $this->assertStringStartsWith('000201', $p->pix_copia_cola);

        $this->post(route('pagamentos.acao', $p), ['acao' => 'receber-pix'])->assertSessionHas('success');
        $this->post(route('pagamentos.acao', $p), ['acao' => 'estornar'])->assertSessionHas('success');

        $this->assertSame('estornado', $p->fresh()->status);
        $this->assertSame(['PAYMENT_CREATED', 'PAYMENT_RECEIVED', 'PAYMENT_REFUNDED'], $this->tipos($p));
    }

    public function test_transicoes_invalidas_sao_bloqueadas(): void
    {
        $this->cobrar(['metodo' => 'pix', 'numero' => null]);
        $p = Pagamento::sole();

        $this->post(route('pagamentos.acao', $p), ['acao' => 'estornar'])->assertSessionHas('error');   // pendente
        $this->post(route('pagamentos.acao', $p), ['acao' => 'liquidar'])->assertSessionHas('error');   // pix
        $this->post(route('pagamentos.acao', $p), ['acao' => 'expirar'])->assertSessionHas('success');
        $this->post(route('pagamentos.acao', $p), ['acao' => 'receber-pix'])->assertSessionHas('error'); // expirado

        $this->assertSame('expirado', $p->fresh()->status);
        $this->assertSame(['PAYMENT_CREATED', 'PAYMENT_OVERDUE'], $this->tipos($p));
    }

    public function test_cartao_liquidado(): void
    {
        $this->cobrar();
        $p = Pagamento::sole();
        $this->post(route('pagamentos.acao', $p), ['acao' => 'liquidar'])->assertSessionHas('success');
        $this->assertSame('recebido', $p->fresh()->status);
    }

    public function test_luhn_e_bandeira(): void
    {
        $this->assertTrue(GatewayFake::luhn('4242424242424242'));
        $this->assertTrue(GatewayFake::luhn('5555555555554444'));
        $this->assertFalse(GatewayFake::luhn('4242424242424241'));
        $this->assertSame('Mastercard', GatewayFake::bandeira('5555555555554444'));
    }

    public function test_telas_abrem(): void
    {
        $this->cobrar();
        foreach (['pagamentos.index', 'pagamentos.create', 'pagamentos.eventos'] as $rota) {
            $this->get(route($rota))->assertOk();
        }
        $this->get(route('pagamentos.show', Pagamento::sole()))->assertOk()->assertSee('PAYMENT_CONFIRMED');
    }
}
