<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Proveedor;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        $owners = User::all();

        // 8 proveedores por dueño (solo para demo)
        $owners->each(function ($u) {
            Proveedor::factory()->count(8)->create([
                'user_duenio_id' => $u->id,
            ]);
        });
    }
}
