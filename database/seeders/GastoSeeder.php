<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gasto;
use App\Models\Corporativo;
use App\Models\Sucursal;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Comprobante;

class GastoSeeder extends Seeder
{
    public function run(): void
    {
        $corps = Corporativo::all();
        $sucs = Sucursal::all();
        $emps = Empleado::all();
        $prov = Proveedor::all();
        $reqs = Requisicion::all();
        $comps = Comprobante::all();

        foreach (range(1, 120) as $i) {
            $linkReq = (rand(1,100) <= 35) ? $reqs->random() : null;
            $linkComp = (rand(1,100) <= 25 && $comps->count() > 0) ? $comps->random() : null;

            $monto = $linkReq ? (float) $linkReq->monto_total : round(mt_rand(5000, 2500000) / 100, 2); // 50.00 a 25,000.00

            Gasto::factory()->create([
                'corporativo_id' => (rand(1,100) <= 70) ? $corps->random()->id : null,
                'sucursal_id' => (rand(1,100) <= 70) ? $sucs->random()->id : null,
                'empleado_id' => (rand(1,100) <= 60) ? $emps->random()->id : null,
                'proveedor_id' => (rand(1,100) <= 55) ? $prov->random()->id : null,
                'requisicion_id' => $linkReq?->id,
                'comprobante_id' => $linkComp?->id,
                'monto'           => $monto,
            ]);
        }
    }
}
