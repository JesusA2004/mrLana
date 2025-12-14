<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RequisicionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'folio' => 'REQ-' . $this->faker->unique()->numerify('########'),
            'tipo' => $this->faker->randomElement(['ANTICIPO','REEMBOLSO']),
            'status' => $this->faker->randomElement(['BORRADOR','CAPTURADA','COMPROBADA','PAGADA','CANCELADA']),
            'comprador_corp_id' => null,
            'sucursal_id' => null,
            'solicitante_id' => null,
            'proveedor_id' => null,
            'concepto_id' => null,
            'monto_subtotal' => 0,
            'monto_iva' => 0,
            'monto_total' => 0,
            'lugar_entrega_texto' => $this->faker->optional()->sentence(4),
            'fecha_entrega' => $this->faker->optional()->dateTimeBetween('now', '+2 months')?->format('Y-m-d'),
            'fecha_captura' => now(),
            'fecha_pago' => $this->faker->optional()->dateTimeBetween('-2 months', '+2 months')?->format('Y-m-d'),
            'beneficiario_pago' => $this->faker->optional()->name(),
            'banco_pago' => $this->faker->optional()->randomElement(['BBVA','Santander','Banorte','HSBC','Citibanamex']),
            'clabe_pago' => $this->faker->optional()->numerify('##################'),
            'cuenta_pago' => $this->faker->optional()->numerify('############'),
            'observaciones' => $this->faker->optional()->paragraph(2),
            'creada_por_user_id' => null,
        ];
    }
}
