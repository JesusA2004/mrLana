<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sucursal;
use App\Models\Area;
use App\Models\Empleado;

class EmpleadoSeeder extends Seeder
{
    public function run(): void
    {
        $areas = Area::all();

        Sucursal::all()->each(function ($suc) use ($areas) {
            Empleado::factory()->count(12)->create([
                'sucursal_id' => $suc->id,
                'area_id' => $areas->random()->id,
                'activo' => true,
            ]);
        });
    }
}
