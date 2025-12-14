<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProveedorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_duenio_id' => null,
            'nombre_comercial' => $this->faker->company(),
            'razon_social' => $this->faker->optional()->company() . ' S.A. de C.V.',
            'rfc' => $this->faker->optional()->bothify('????######???'),
            'direccion' => $this->faker->optional()->address(),
            'contacto' => $this->faker->optional()->name(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'email' => $this->faker->optional()->companyEmail(),
            'beneficiario' => $this->faker->optional()->name(),
            'banco' => $this->faker->optional()->randomElement(['BBVA','Santander','Banorte','HSBC','Citibanamex']),
            'cuenta' => $this->faker->optional()->numerify('############'),
            'clabe' => $this->faker->optional()->numerify('##################'),
        ];
    }
}
