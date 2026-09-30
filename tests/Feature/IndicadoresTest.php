<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\Pedido;
use App\Models\Produto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadoresTest extends TestCase
{
    use RefreshDatabase;

    private function pedido(string $data, float $preco, int $qtd, string $status = 'entregue'): void
    {
        $produto = Produto::factory()->create(['nome' => "P{$preco}", 'categoria' => 'Informática', 'preco' => $preco]);
        $pedido = Pedido::forceCreate(['cliente_id' => Cliente::factory()->create()->id, 'status' => $status,
            'total' => $preco * $qtd, 'created_at' => $data, 'updated_at' => $data]);
        $pedido->itens()->create(['produto_id' => $produto->id, 'quantidade' => $qtd, 'preco_unitario' => $preco]);
    }

    public function test_dados_do_periodo_com_comparacao_e_series(): void
    {
        $this->actingAs(User::factory()->create());
        $this->travelTo('2026-09-10 12:00');

        $this->pedido('2026-09-02 10:00', 100, 2);                // atual
        $this->pedido('2026-09-05 10:00', 50, 1);                 // atual
        $this->pedido('2026-09-06 10:00', 999, 1, 'cancelado');   // ignorado
        $this->pedido('2026-08-28 10:00', 125, 1);                // período anterior (27/08–31/08)
        Chamado::forceCreate(['cliente_id' => Cliente::first()->id, 'titulo' => 'x', 'descricao' => 'x', 'prioridade' => 'alta',
            'status' => 'resolvido', 'prazo_sla' => '2026-09-03 18:00', 'resolvido_em' => '2026-09-03 12:00',
            'created_at' => '2026-09-03 10:00', 'updated_at' => '2026-09-03 12:00']);

        $r = $this->getJson(route('indicadores.dados', ['inicio' => '2026-09-01', 'fim' => '2026-09-05']))->assertOk();

        $r->assertJsonPath('periodo.anterior', ['2026-08-27', '2026-08-31'])
            ->assertJsonPath('periodo.granularidade', 'dia')
            ->assertJsonPath('kpis.faturamento', ['valor' => 250, 'anterior' => 125])
            ->assertJsonPath('kpis.pedidos', ['valor' => 2, 'anterior' => 1])
            ->assertJsonPath('kpis.ticket.valor', 125)
            ->assertJsonPath('kpis.sla', ['valor' => 100, 'anterior' => null])
            ->assertJsonPath('faturamento.labels', ['01/09', '02/09', '03/09', '04/09', '05/09'])
            ->assertJsonPath('faturamento.valores', [0, 200, 0, 0, 50])
            ->assertJsonPath('chamados.abertos', [0, 0, 1, 0, 0])
            ->assertJsonPath('chamados.resolvidos', [0, 0, 1, 0, 0])
            ->assertJsonPath('topProdutos.0.nome', 'P100')
            ->assertJsonCount(2, 'topProdutos')
            ->assertJsonPath('categorias.0.nome', 'Informática');
        $this->assertEquals(250, $r->json('categorias.0.valor'));
    }

    public function test_periodo_longo_agrupa_por_mes_e_valida_limites(): void
    {
        $this->actingAs(User::factory()->create());

        $this->getJson(route('indicadores.dados', ['inicio' => '2025-10-01', 'fim' => '2026-09-30']))
            ->assertOk()->assertJsonPath('periodo.granularidade', 'mês')->assertJsonCount(12, 'faturamento.labels');

        $this->getJson(route('indicadores.dados', ['inicio' => '2026-09-10', 'fim' => '2026-09-01']))->assertUnprocessable();
        $this->getJson(route('indicadores.dados', ['inicio' => '2024-01-01', 'fim' => '2026-09-01']))->assertUnprocessable();
    }

    public function test_painel_abre(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('indicadores.index'))->assertOk()->assertSee('g-faturamento');
    }
}
