<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ajuste;
use App\Models\Requisicion;
use App\Models\User;

class AjusteSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        Requisicion::whereIn('status', ['COMPROBADA','PAGADA'])->get()->each(function ($req) use ($users) {
            if (rand(1,100) <= 25) {
                Ajuste::factory()->create([
                    'requisicion_id' => $req->id,
                    'user_registro_id' => $users->random()->id,
                    'fecha_registro' => now()->subDays(rand(0, 120)),
                ]);
            }
        });
    }
}
