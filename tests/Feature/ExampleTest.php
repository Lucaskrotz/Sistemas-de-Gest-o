<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lista_os_modulos(): void
    {
        $this->get('/')->assertOk()->assertSee('Gestor ERP')->assertSee(route('demo', 'chamados'));
    }

    public function test_demo_loga_usuario_do_modulo(): void
    {
        $user = User::factory()->create(['email' => 'erp@demo.test']);

        $this->get('/demo/erp')->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function test_demo_de_modulo_inexistente_da_404(): void
    {
        $this->get('/demo/nada')->assertNotFound();
        $this->assertGuest();
    }
}
