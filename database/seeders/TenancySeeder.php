<?php

namespace Database\Seeders;

use App\Models\Contato;
use App\Models\Oportunidade;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenancySeeder extends Seeder
{
    private const EMPRESAS = [
        [
            'tenant' => ['nome' => 'Aurora Tecnologia', 'slug' => 'aurora', 'cor' => '#2563eb', 'plano' => 'Pro'],
            'admin' => 'tenancy@demo.test', // usuário do /demo/tenancy
            'dominio' => 'aurora.test',
            'equipe' => 3, 'contatos' => 18, 'oportunidades' => 16,
            'titulos' => ['Implantação de CRM', 'Renovação anual', 'Expansão de licenças', 'Consultoria de dados', 'Integração com ERP', 'Treinamento de equipe'],
        ],
        [
            'tenant' => ['nome' => 'Brisa Logística', 'slug' => 'brisa', 'cor' => '#ea580c', 'plano' => 'Básico'],
            'admin' => 'admin@brisa.test',
            'dominio' => 'brisa.test',
            'equipe' => 2, 'contatos' => 12, 'oportunidades' => 10,
            'titulos' => ['Contrato de frete mensal', 'Armazenagem', 'Roteirização de entregas', 'Frota dedicada', 'Logística reversa'],
        ],
    ];

    public function run(): void
    {
        foreach (self::EMPRESAS as $cfg) {
            $tenant = Tenant::create($cfg['tenant']);

            $admin = User::firstWhere('email', $cfg['admin'])
                ?? User::factory()->create(['email' => $cfg['admin']]);
            $admin->forceFill(['tenant_id' => $tenant->id, 'name' => "Admin {$tenant->nome}"])->save();
            $membros = User::factory($cfg['equipe'])
                ->sequence(fn ($s) => ['email' => "usuario{$s->index}@{$cfg['dominio']}"])
                ->create(['tenant_id' => $tenant->id])
                ->prepend($admin);

            // Dados criados "dentro" do tenant: o trait preenche tenant_id sozinho.
            Tenant::comoTenant($tenant, function () use ($cfg, $membros) {
                $contatos = Contato::factory($cfg['contatos'])->create();
                // Mesmo e-mail nas duas empresas: unicidade é por tenant, não global.
                $contatos->push(Contato::create(['nome' => 'Compras (cliente em comum)', 'email' => 'compras@cliente-comum.test', 'empresa' => 'Cliente em Comum S.A.']));

                foreach (range(1, $cfg['oportunidades']) as $_) {
                    Oportunidade::create([
                        'contato_id' => $contatos->random()->id,
                        'responsavel_id' => $membros->random()->id,
                        'titulo' => fake()->randomElement($cfg['titulos']),
                        'valor' => fake()->randomElement([4500, 8900, 12000, 18500, 25000, 42000, 60000]),
                        'etapa' => fake()->randomElement(['prospeccao', 'prospeccao', 'proposta', 'proposta', 'negociacao', 'ganho', 'perdido']),
                    ]);
                }
            });
        }
    }
}
