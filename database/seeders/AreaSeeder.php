<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Corporativo;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        Corporativo::all()->each(function ($corp) {
            Area::factory()->count(6)->create([
                'corporativo_id' => $corp->id,
            ]);
        });

        // algunas áreas globales (corporativo_id null)
        Area::factory()->count(2)->create(['corporativo_id' => null]);
    }
}
