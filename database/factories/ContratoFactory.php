<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContratoFactory extends Factory
{
    public function definition(): array
    {
        $capital = $this->faker->randomFloat(2, 20000, 500000);
        $tasaAnual = $this->faker->randomFloat(5, 0.05, 0.28); // 0.05000 a 0.28000
        $tasaMensual = $tasaAnual / 12;

        $bruto = $capital * $tasaMensual;
        $ret = $bruto * 0.10;
        $neto = $bruto - $ret;

        $fechaContrato = $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d');
        $plazo = $this->faker->numberBetween(3, 36);

        return [
            'inversionista_id' => null,
            'corporativo_id' => null,
            'no_contrato' => 'CT-' . $this->faker->unique()->numerify('########'),
            'fecha_contrato' => $fechaContrato,
            'capital_inicial' => $capital,
            'fecha_reembolso' => $this->faker->dateTimeBetween('now', '+3 years')->format('Y-m-d'),
            'plazo_meses' => $plazo,
            'tasa_anual' => $tasaAnual,
            'tasa_mensual' => $tasaMensual,
            'banco' => $this->faker->randomElement(['BBVA','Santander','Banorte','HSBC','Citibanamex']),
            'clabe' => $this->faker->numerify('##################'),
            'cuenta' => $this->faker->numerify('############'),
            'rendimiento_bruto_mensual' => $bruto,
            'retencion_mensual' => $ret,
            'rendimiento_neto_mensual' => $neto,
            'periodicidad_pago' => $this->faker->randomElement(['mensual','quincenal','semanal','unico']),
            'dia_pago' => $this->faker->numberBetween(1, 28),
            'status' => $this->faker->randomElement(['CAPTURADA','ACTIVA','VENCIDA','CANCELADA']),
        ];
    }
}
