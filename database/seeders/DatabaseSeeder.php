<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// Sem WithoutModelEvents: o TenancySeeder depende do evento "creating" do BelongsToTenant.
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Um usuário demo por módulo, usado por /demo/{modulo}.
        foreach (config('modulos') as $chave => $modulo) {
            User::factory()->create([
                'name' => "Demo {$modulo['nome']}",
                'email' => "{$chave}@demo.test",
            ]);
        }

        $this->call([ErpSeeder::class, ChamadosSeeder::class, FinanceiroSeeder::class, PagamentosSeeder::class, TenancySeeder::class]);
    }
}
