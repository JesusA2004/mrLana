<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ConceptoFactory extends Factory
{
    public function definition(): array
    {
        $grupo = $this->faker->randomElement(['SERVICIOS','INSUMOS','MANTENIMIENTO','VIATICOS','SOFTWARE','OTROS']);

        return [
            'grupo' => $grupo,
            'nombre' => $grupo . ' - ' . $this->faker->words(2, true),
            'activo' => true,
        ];
    }
}
