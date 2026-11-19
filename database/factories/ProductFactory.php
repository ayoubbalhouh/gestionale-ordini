<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => strtoupper(fake()->unique()->bothify('PRD-####')),
            'nome' => ucfirst(fake()->words(2, true)),
            'prezzo_cents' => fake()->numberBetween(100, 5000),
            'iva' => 22,
            'giacenza' => 50,
            'scorta_minima' => 5,
            'attivo' => true,
        ];
    }
}
