<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PagoFactory extends Factory
{
    public function definition(): array
    {
        $bruto = $this->faker->randomFloat(2, 300, 20000);
        $ret = $this->faker->randomFloat(2, 0, $bruto * 0.2);
        $neto = max(0, $bruto - $ret);

        return [
            'contrato_id' => null,
            'fecha_pago' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'rendimiento_bruto' => $bruto,
            'retenciones' => $ret,
            'rendimiento_neto' => $neto,
            'status' => $this->faker->randomElement(['PENDIENTE','PAGADO','CANCELADO']),
            'recibo_pago_ruta' => $this->faker->optional()->randomElement([
                null, 'recibos/' . $this->faker->uuid() . '.pdf'
            ]),
        ];
    }
}
