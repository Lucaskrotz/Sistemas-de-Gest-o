<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    public function definition(): array
    {
        $empresa = fake()->boolean(60);

        return [
            'nome' => $empresa ? fake()->company() : fake()->name(),
            'documento' => $empresa ? fake()->unique()->cnpj() : fake()->unique()->cpf(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => fake()->cellphoneNumber(),
            'cidade' => fake()->city(),
            'uf' => fake()->stateAbbr(),
            'ativo' => fake()->boolean(90),
        ];
    }
}
