<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class IngresoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fecha_ingreso' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'corporativo_id' => null,
            'sucursal_id' => null,
            'inversionista_id' => null,
            'monto' => $this->faker->randomFloat(2, 200, 250000),
            'moneda' => 'MXN',
            'origen' => $this->faker->randomElement(['VENTA','APORTE_INVERSIONISTA','OTRO']),
            'descripcion' => $this->faker->optional()->sentence(10),
        ];
    }
}
