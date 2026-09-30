<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProdutoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => fake()->unique()->bothify('SKU-####'),
            'nome' => ucfirst(fake()->words(2, true)),
            'categoria' => fake()->randomElement(['Informática', 'Periféricos', 'Escritório']),
            'preco' => fake()->randomFloat(2, 20, 3000),
            'estoque' => fake()->numberBetween(0, 200),
            'ativo' => true,
        ];
    }
}
