<?php

namespace Tests\Feature;

use App\Models\Contato;
use App\Models\Oportunidade;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class TenancyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $a;
    private Tenant $b;
    private User $userA;
    private User $userB;
    private Contato $contatoB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = Tenant::create(['nome' => 'Aurora', 'slug' => 'aurora', 'cor' => '#2563eb', 'plano' => 'Pro']);
        $this->b = Tenant::create(['nome' => 'Brisa', 'slug' => 'brisa', 'cor' => '#ea580c', 'plano' => 'Básico']);
        $this->userA = User::factory()->create(['tenant_id' => $this->a->id]);
        $this->userB = User::factory()->create(['tenant_id' => $this->b->id]);

        Tenant::comoTenant($this->a, fn () => Contato::create(['nome' => 'Contato da Aurora', 'email' => 'x@a.test']));
        $this->contatoB = Tenant::comoTenant($this->b, fn () => Contato::create(['nome' => 'Contato da Brisa', 'email' => 'x@b.test']));
    }

    public function test_cada_empresa_so_ve_os_proprios_dados(): void
    {
        $this->actingAs($this->userA)->get(route('tenancy.contatos.index'))
            ->assertOk()->assertSee('Contato da Aurora')->assertDontSee('Contato da Brisa');

        $this->actingAs($this->userB)->get(route('tenancy.contatos.index'))
            ->assertOk()->assertSee('Contato da Brisa')->assertDontSee('Contato da Aurora');
    }

    public function test_id_de_outra_empresa_na_url_da_404(): void
    {
        $this->actingAs($this->userA);
        $this->get(route('tenancy.contatos.show', $this->contatoB->id))->assertNotFound();
        $this->delete(route('tenancy.contatos.destroy', $this->contatoB->id))->assertNotFound();

        $oportB = Tenant::comoTenant($this->b, fn () => Oportunidade::create(['contato_id' => $this->contatoB->id, 'titulo' => 'B', 'valor' => 10, 'etapa' => 'proposta']));
        $this->patch(route('tenancy.oportunidades.update', $oportB->id), ['etapa' => 'ganho'])->assertNotFound();

        $this->assertDatabaseHas('contatos', ['id' => $this->contatoB->id]);
        $this->assertDatabaseHas('oportunidades', ['id' => $oportB->id, 'etapa' => 'proposta']);
    }

    public function test_criacao_preenche_tenant_e_ignora_tenant_id_enviado(): void
    {
        $this->actingAs($this->userA)->post(route('tenancy.contatos.store'), [
            'nome' => 'Novo', 'email' => 'novo@a.test', 'tenant_id' => $this->b->id, // tentativa de injeção
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('contatos', ['email' => 'novo@a.test', 'tenant_id' => $this->a->id]);
    }

    public function test_email_unico_por_empresa_nao_global(): void
    {
        $this->actingAs($this->userA);
        $this->post(route('tenancy.contatos.store'), ['nome' => 'Dup', 'email' => 'x@a.test'])->assertSessionHasErrors('email');
        $this->post(route('tenancy.contatos.store'), ['nome' => 'Mesmo e-mail da Brisa', 'email' => 'x@b.test'])->assertSessionHasNoErrors();
    }

    public function test_nao_referencia_contato_ou_usuario_de_outra_empresa(): void
    {
        $this->actingAs($this->userA);
        $this->post(route('tenancy.oportunidades.store'), ['titulo' => 'X', 'valor' => 100, 'contato_id' => $this->contatoB->id])
            ->assertSessionHasErrors('contato_id');

        $contatoA = Tenant::comoTenant($this->a, fn () => Contato::first());
        $this->post(route('tenancy.oportunidades.store'), ['titulo' => 'X', 'valor' => 100, 'contato_id' => $contatoA->id, 'responsavel_id' => $this->userB->id])
            ->assertSessionHasErrors('responsavel_id');

        $this->assertDatabaseCount('oportunidades', 0);
    }

    public function test_sem_tenant_definido_nada_e_lido_nem_criado(): void
    {
        Tenant::definir(null);
        $this->assertSame(0, Contato::count()); // fail-closed

        $this->expectException(LogicException::class);
        Contato::create(['nome' => 'Órfão', 'email' => 'o@x.test']);
    }

    public function test_tenant_id_nao_pode_ser_alterado(): void
    {
        $this->expectException(LogicException::class);
        Tenant::comoTenant($this->b, function () {
            $this->contatoB->tenant_id = $this->a->id;
            $this->contatoB->save();
        });
    }

    public function test_trocar_de_empresa_e_usuario_sem_empresa(): void
    {
        $this->actingAs($this->userA)->post(route('tenancy.trocar', $this->b))->assertRedirect(route('tenancy.index'));
        $this->assertAuthenticatedAs($this->userB);
        $this->get(route('tenancy.index'))->assertOk()->assertSee('Brisa')->assertSee('tenant_id = '.$this->b->id);

        $this->actingAs(User::factory()->create())->get(route('tenancy.index'))->assertForbidden();
    }

    public function test_telas_abrem(): void
    {
        $this->actingAs($this->userA);
        foreach (['tenancy.index', 'tenancy.equipe', 'tenancy.contatos.index', 'tenancy.oportunidades.index'] as $rota) {
            $this->get(route($rota))->assertOk();
        }
    }
}
