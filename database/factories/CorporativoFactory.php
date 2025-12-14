<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CorporativoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'rfc' => $this->faker->optional()->bothify('????######???'),
            'direccion' => $this->faker->optional()->address(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->companyEmail(),
            'codigo' => $this->faker->optional()->bothify('CORP-###'),
            'logo_path' => null,
            'activo' => true,
        ];
    }
}
