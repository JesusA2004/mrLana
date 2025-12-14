<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AjusteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requisicion_id' => null,
            'tipo' => $this->faker->randomElement(['DEVOLUCION','FALTANTE']),
            'monto' => $this->faker->randomFloat(2, 10, 20000),
            'estatus' => $this->faker->randomElement(['PENDIENTE','APLICADO']),
            'fecha_registro' => now(),
            'user_registro_id' => null,
            'notas' => $this->faker->optional()->sentence(10),
        ];
    }
}
