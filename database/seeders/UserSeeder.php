<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empleado;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $empleados = Empleado::where('activo', true)->get();

        $empleadoAdmin = $empleados->random();
        User::factory()->admin()->create([
            'empleado_id' => $empleadoAdmin->id,
            'name' => 'Admin Sistema',
            'email' => 'admin@demo.local',
        ]);

        $empleados->take(25)->each(function ($emp) {
            User::factory()->create([
                'empleado_id' => $emp->id,
            ]);
        });
    }
}
