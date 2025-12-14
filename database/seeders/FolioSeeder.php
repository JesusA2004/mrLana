<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Folio;
use App\Models\User;

class FolioSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        Folio::factory()->count(60)->create([
            'user_registro_id' => $users->random()->id,
        ]);
    }
}
