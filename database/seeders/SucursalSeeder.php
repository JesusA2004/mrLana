<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Corporativo;
use App\Models\Sucursal;

class SucursalSeeder extends Seeder
{
    public function run(): void
    {
        Corporativo::all()->each(function ($corp) {
            Sucursal::factory()->count(3)->create([
                'corporativo_id' => $corp->id,
            ]);
        });
    }
}
