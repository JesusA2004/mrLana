<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inversionista;

class InversionistaSeeder extends Seeder
{
    public function run(): void
    {
        Inversionista::factory()->count(15)->create();
    }
}
