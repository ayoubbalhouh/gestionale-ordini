<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ragione_sociale' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'citta' => fake()->city(),
        ];
    }
}
