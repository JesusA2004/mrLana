<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DepartamentoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sucursal_id' => null,
            'nombre' => $this->faker->randomElement([
                'Administración', 'Compras', 'Contabilidad', 'Operaciones', 'TI', 'RH'
            ]),
            'descripcion' => $this->faker->optional()->sentence(10),
            'activo' => true,
        ];
    }
}
