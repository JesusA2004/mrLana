<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FolioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'folio' => 'F-' . $this->faker->unique()->numerify('##########'),
            'rfc_emisor' => $this->faker->optional()->bothify('????######???'),
            'rfc_receptor' => $this->faker->optional()->bothify('????######???'),
            'monto_total' => $this->faker->optional()->randomFloat(2, 50, 90000),
            'origen' => $this->faker->randomElement(['SISTEMA','MANUAL']),
            'user_registro_id' => null,
        ];
    }
}
