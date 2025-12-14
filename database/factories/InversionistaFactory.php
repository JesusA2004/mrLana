<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InversionistaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name(),
            'rfc' => $this->faker->optional()->bothify('????######???'),
            'direccion' => $this->faker->optional()->address(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->safeEmail(),
        ];
    }
}
