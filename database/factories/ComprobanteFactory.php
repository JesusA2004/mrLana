<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ComprobanteFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 100, 50000);
        $iva = round($subtotal * 0.16, 2);
        $total = round($subtotal + $iva, 2);

        return [
            'requisicion_id' => null,
            'proveedor_id' => null,
            'tipo_doc' => $this->faker->randomElement(['FACTURA','TICKET','NOTA','OTRO']),
            'uuid_cfdi' => $this->faker->optional(0.7)->uuid(),
            'folio' => $this->faker->optional()->bothify('FOL-#####'),
            'rfc_emisor' => $this->faker->optional()->bothify('????######???'),
            'rfc_receptor' => $this->faker->optional()->bothify('????######???'),
            'subtotal' => $subtotal,
            'iva' => $iva,
            'total' => $total,
            'estatus' => $this->faker->randomElement(['CARGADO','EN_REVISION','VALIDADO','RECHAZADO']),
            'fecha_emision' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')?->format('Y-m-d'),
            'fecha_carga' => now(),
            'user_carga_id' => null,
        ];
    }
}
