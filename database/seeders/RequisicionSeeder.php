<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Requisicion;
use App\Models\Corporativo;
use App\Models\Sucursal;
use App\Models\Empleado;
use App\Models\Concepto;
use App\Models\Proveedor;
use App\Models\User;

class RequisicionSeeder extends Seeder
{
    public function run(): void
    {
        $corps = Corporativo::all();
        $sucs = Sucursal::all();
        $emps = Empleado::all();
        $conceptos = Concepto::all();
        $users = User::all();
        $proveedores = Proveedor::all();

        foreach (range(1, 80) as $i) {
            $suc = $sucs->random();

            Requisicion::factory()->create([
                'comprador_corp_id' => $corps->random()->id,
                'sucursal_id' => $suc->id,
                'solicitante_id' => $emps->where('sucursal_id', $suc->id)->random()->id ?? $emps->random()->id,
                'proveedor_id' => (rand(1, 100) <= 60) ? $proveedores->random()->id : null,
                'concepto_id' => $conceptos->random()->id,
                'creada_por_user_id' => $users->random()->id,
                'fecha_captura' => now()->subDays(rand(0, 120)),
            ]);
        }
    }
}
