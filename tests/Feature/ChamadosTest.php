<?php

namespace Tests\Feature;

use App\Models\Chamado;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChamadosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['name' => 'Atendente Teste']));
    }

    private function abrir(string $prioridade = 'alta'): Chamado
    {
        $this->post(route('chamados.store'), [
            'cliente_id' => Cliente::factory()->create()->id,
            'titulo' => 'Sistema fora do ar',
            'descricao' => 'Não abre.',
            'prioridade' => $prioridade,
        ])->assertSessionHasNoErrors();

        return Chamado::latest('id')->first();
    }

    public function test_abrir_define_sla_pela_prioridade_e_registra_historico(): void
    {
        $this->freezeSecond();
        $chamado = $this->abrir('urgente');

        $this->assertTrue($chamado->prazo_sla->eq(now()->addHours(4)));
        $this->assertSame('aberto', $chamado->status);
        $this->assertSame('Chamado aberto', $chamado->historicos->first()->descricao);
        $this->assertSame('Atendente Teste', $chamado->historicos->first()->autor);
    }

    public function test_telas_abrem(): void
    {
        $chamado = $this->abrir();

        $this->get(route('chamados.index'))->assertOk()->assertSee($chamado->titulo);
        $this->get(route('chamados.create'))->assertOk();
        $this->get(route('chamados.show', $chamado))->assertOk()->assertSee('Chamado aberto');
    }

    public function test_kanban_move_via_json_e_resolve_dentro_do_sla(): void
    {
        $chamado = $this->abrir();

        $this->patchJson(route('chamados.update', $chamado), ['status' => 'resolvido'])
            ->assertOk()->assertJson(['sla' => ['texto' => 'SLA cumprido', 'cor' => 'success']]);

        $chamado->refresh();
        $this->assertNotNull($chamado->resolvido_em);
        $this->assertSame('Status alterado: Aberto → Resolvido', $chamado->historicos->first()->descricao);

        // reabrir limpa a resolução
        $this->patchJson(route('chamados.update', $chamado), ['status' => 'andamento'])->assertOk();
        $this->assertNull($chamado->fresh()->resolvido_em);
    }

    public function test_sla_vencido_e_violado(): void
    {
        $chamado = $this->abrir('urgente');

        $this->travel(5)->hours();
        $this->assertSame('danger', $chamado->fresh()->sla()[1]);
        $this->assertStringStartsWith('Vencido há', $chamado->fresh()->sla()[0]);

        $this->patchJson(route('chamados.update', $chamado), ['status' => 'resolvido'])
            ->assertJson(['sla' => ['texto' => 'SLA violado']]);
    }

    public function test_status_invalido_e_rejeitado(): void
    {
        $chamado = $this->abrir();

        $this->patchJson(route('chamados.update', $chamado), ['status' => 'xyz'])->assertUnprocessable();
        $this->assertSame('aberto', $chamado->fresh()->status);
    }

    public function test_comentario_e_responsavel_entram_no_historico(): void
    {
        $chamado = $this->abrir();

        $this->post(route('chamados.comentar', $chamado), ['comentario' => 'Verificando logs.']);
        $this->patch(route('chamados.update', $chamado), ['status' => 'aberto', 'responsavel' => 'Ana Souza']);

        $historico = $chamado->fresh()->historicos->pluck('descricao');
        $this->assertContains('Verificando logs.', $historico);
        $this->assertContains('Responsável: Ana Souza', $historico);
        $this->assertCount(3, $historico); // aberto + comentário + responsável (status igual não gera evento)
    }
}
