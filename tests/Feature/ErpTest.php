<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_telas_abrem(): void
    {
        Cliente::factory()->create();
        Produto::factory()->create();

        foreach (['erp.index', 'erp.clientes.index', 'erp.clientes.create', 'erp.produtos.index', 'erp.pedidos.index', 'erp.pedidos.create'] as $rota) {
            $this->get(route($rota))->assertOk();
        }
    }

    public function test_visitante_vai_para_login_demo(): void
    {
        auth()->logout();
        $this->get('/erp/clientes')->assertRedirect(route('demo', 'erp'));
    }

    public function test_cadastra_cliente_e_valida_documento_unico(): void
    {
        $dados = ['nome' => 'ACME Ltda', 'documento' => '12.345.678/0001-90', 'email' => 'contato@acme.test', 'ativo' => '1'];

        $this->post(route('erp.clientes.store'), $dados)->assertRedirect(route('erp.clientes.index'));
        $this->assertDatabaseHas('clientes', ['documento' => '12.345.678/0001-90', 'ativo' => true]);

        $this->post(route('erp.clientes.store'), $dados)->assertSessionHasErrors('documento');
    }

    public function test_pedido_calcula_total_e_baixa_estoque(): void
    {
        $cliente = Cliente::factory()->create(['ativo' => true]);
        $a = Produto::factory()->create(['preco' => 100, 'estoque' => 10]);
        $b = Produto::factory()->create(['preco' => 25.50, 'estoque' => 5]);

        $this->post(route('erp.pedidos.store'), [
            'cliente_id' => $cliente->id,
            'itens' => [['produto_id' => $a->id, 'quantidade' => 3], ['produto_id' => $b->id, 'quantidade' => 2]],
        ])->assertSessionHasNoErrors();

        $pedido = Pedido::sole();
        $this->assertEquals(351.00, $pedido->total);
        $this->assertSame('pendente', $pedido->status);
        $this->assertSame(7, $a->fresh()->estoque);
        $this->assertSame(3, $b->fresh()->estoque);
    }

    public function test_pedido_sem_estoque_e_rejeitado_sem_efeitos(): void
    {
        $cliente = Cliente::factory()->create(['ativo' => true]);
        $produto = Produto::factory()->create(['estoque' => 2]);

        $this->post(route('erp.pedidos.store'), [
            'cliente_id' => $cliente->id,
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 3]],
        ])->assertSessionHasErrors('itens.0.quantidade');

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertSame(2, $produto->fresh()->estoque);
    }

    public function test_cancelar_devolve_estoque_e_trava_status(): void
    {
        $cliente = Cliente::factory()->create(['ativo' => true]);
        $produto = Produto::factory()->create(['estoque' => 10]);
        $this->post(route('erp.pedidos.store'), [
            'cliente_id' => $cliente->id,
            'itens' => [['produto_id' => $produto->id, 'quantidade' => 4]],
        ]);
        $pedido = Pedido::sole();

        $this->patch(route('erp.pedidos.status', $pedido), ['status' => 'cancelado']);
        $this->assertSame(10, $produto->fresh()->estoque);

        $this->patch(route('erp.pedidos.status', $pedido), ['status' => 'pago'])->assertSessionHas('error');
        $this->assertSame('cancelado', $pedido->fresh()->status);
    }

    public function test_nao_exclui_cliente_com_pedidos(): void
    {
        $cliente = Cliente::factory()->create();
        Pedido::create(['cliente_id' => $cliente->id]);

        $this->delete(route('erp.clientes.destroy', $cliente))->assertSessionHas('error');
        $this->assertModelExists($cliente);
    }
}
