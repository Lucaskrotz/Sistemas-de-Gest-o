<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/** tenant_id vem do tenant atual (BelongsToTenant); use dentro de Tenant::comoTenant(). */
class ContatoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nome' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'empresa' => fake()->company(),
            'telefone' => fake()->cellphoneNumber(),
        ];
    }
}
