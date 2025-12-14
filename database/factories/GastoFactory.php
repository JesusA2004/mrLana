<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GastoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fecha_gasto' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'corporativo_id' => null,
            'sucursal_id' => null,
            'empleado_id' => null,
            'proveedor_id' => null,
            'monto' => $this->faker->randomFloat(2, 50, 120000),
            'moneda' => 'MXN',
            'tipo_gasto' => $this->faker->randomElement(['OPERATIVO','RUTA','VIAJE','CAJA_CHICA','OTRO']),
            'metodo_pago' => $this->faker->randomElement([
                'EFECTIVO','TRANSFERENCIA','TARJETA_CREDITO','TARJETA_DEBITO','CHEQUE','OTRO'
            ]),
            'estatus_validacion' => $this->faker->randomElement(['PENDIENTE','VALIDADO','RECHAZADO']),
            'requisicion_id' => null,
            'comprobante_id' => null,
            'descripcion' => $this->faker->optional()->sentence(12),
        ];
    }
}
