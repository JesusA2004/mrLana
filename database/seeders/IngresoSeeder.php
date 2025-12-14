<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ingreso;
use App\Models\Corporativo;
use App\Models\Sucursal;
use App\Models\Inversionista;

class IngresoSeeder extends Seeder
{
    public function run(): void
    {
        $corps = Corporativo::all();
        $sucs = Sucursal::all();
        $invs = Inversionista::all();

        foreach (range(1, 80) as $i) {
            $origen = collect(['VENTA','APORTE_INVERSIONISTA','OTRO'])->random();

            Ingreso::factory()->create([
                'corporativo_id' => (rand(1,100) <= 70) ? $corps->random()->id : null,
                'sucursal_id' => (rand(1,100) <= 60) ? $sucs->random()->id : null,
                'inversionista_id' => $origen === 'APORTE_INVERSIONISTA' ? $invs->random()->id : null,
                'origen' => $origen,
            ]);
        }
    }
}
