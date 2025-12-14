<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmpleadoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sucursal_id' => null,
            'area_id' => null,
            'nombre' => $this->faker->firstName(),
            'apellido_paterno' => $this->faker->lastName(),
            'apellido_materno' => $this->faker->optional()->lastName(),
            'email' => $this->faker->optional(0.7)->safeEmail(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'puesto' => $this->faker->optional()->randomElement(['Auxiliar','Analista','Supervisor','Gerente']),
            'activo' => true,
        ];
    }
}
