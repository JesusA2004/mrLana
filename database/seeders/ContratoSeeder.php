<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Contrato;
use App\Models\Inversionista;
use App\Models\Corporativo;

class ContratoSeeder extends Seeder
{
    public function run(): void
    {
        $invs = Inversionista::all();
        $corps = Corporativo::all();

        foreach (range(1, 25) as $i) {
            Contrato::factory()->create([
                'inversionista_id' => $invs->random()->id,
                'corporativo_id' => (rand(1, 100) <= 70) ? $corps->random()->id : null,
            ]);
        }
    }
}
