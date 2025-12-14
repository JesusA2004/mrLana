<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Detalle;
use App\Models\Requisicion;
use App\Models\Sucursal;

class DetalleSeeder extends Seeder
{
    public function run(): void
    {
        $sucs = Sucursal::all();

        Requisicion::all()->each(function ($req) use ($sucs) {
            $subtotal = 0; $iva = 0; $total = 0;

            $n = rand(1, 6);
            for ($i=0; $i<$n; $i++) {
                $det = Detalle::factory()->create([
                    'requisicion_id' => $req->id,
                    'sucursal_id' => (rand(1,100) <= 50) ? $sucs->random()->id : null,
                ]);

                $subtotal += (float)$det->subtotal;
                $iva += (float)$det->iva;
                $total += (float)$det->total;
            }

            $req->update([
                'monto_subtotal' => round($subtotal, 2),
                'monto_iva' => round($iva, 2),
                'monto_total' => round($total, 2),
            ]);
        });
    }
}
