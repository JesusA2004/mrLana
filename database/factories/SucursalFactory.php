<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SucursalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'corporativo_id' => null,
            'nombre' => 'Sucursal ' . $this->faker->city(),
            'codigo' => $this->faker->optional()->bothify('SUC-###'),
            'ciudad' => $this->faker->optional()->city(),
            'estado' => $this->faker->optional()->state(),
            'direccion' => $this->faker->optional()->address(),
            'activo' => true,
        ];
    }
}
