<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comprobante;
use App\Models\Requisicion;
use App\Models\User;
use App\Models\Proveedor;

class ComprobanteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $proveedores = Proveedor::all();

        Requisicion::whereIn('status', ['COMPROBADA','PAGADA'])->get()->each(function ($req) use ($users, $proveedores) {
            Comprobante::factory()->count(rand(1, 2))->create([
                'requisicion_id' => $req->id,
                'proveedor_id' => $req->proveedor_id ?? (rand(1,100) <= 60 ? $proveedores->random()->id : null),
                'subtotal' => $req->monto_subtotal,
                'iva' => $req->monto_iva,
                'total' => $req->monto_total,
                'user_carga_id' => $users->random()->id,
                'fecha_carga' => now()->subDays(rand(0, 120)),
            ]);
        });
    }
}
