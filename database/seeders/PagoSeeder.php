<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pago;
use App\Models\Contrato;

class PagoSeeder extends Seeder
{
    public function run(): void
    {
        Contrato::all()->each(function ($ct) {
            // pagos simulados
            Pago::factory()->count(rand(1, 6))->create([
                'contrato_id' => $ct->id,
            ]);
        });
    }
}
