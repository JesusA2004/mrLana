<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'corporativo_id' => null,
            'nombre' => $this->faker->randomElement(['Compras','Contabilidad','Operaciones','TI','RH','Dirección']),
            'activo' => true,
        ];
    }
}
