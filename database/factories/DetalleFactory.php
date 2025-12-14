<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleFactory extends Factory
{
    public function definition(): array
    {
        $cantidad = $this->faker->randomFloat(2, 1, 15);
        $precio = $this->faker->randomFloat(2, 20, 5000);
        $subtotal = round($cantidad * $precio, 2);
        $iva = round($subtotal * 0.16, 2);
        $total = round($subtotal + $iva, 2);

        return [
            'requisicion_id' => null,
            'sucursal_id' => null,
            'cantidad' => $cantidad,
            'descripcion' => $this->faker->sentence(6),
            'precio_unitario' => $precio,
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
        ];
    }
}
