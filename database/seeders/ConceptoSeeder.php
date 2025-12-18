<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Concepto;

class ConceptoSeeder extends Seeder
{
    public function run(): void
    {
        // catálogo más real (y estable) que puro random
        $data = [
            ['SERVICIOS','Servicios en la nube'],
            ['SERVICIOS','Internet'],
            ['SOFTWARE','Licencias'],
            ['INSUMOS','Papelería'],
            ['MANTENIMIENTO','Mantenimiento general'],
            ['VIATICOS','Viáticos'],
            ['OTROS','Otros'],
        ];

        foreach ($data as [$nombre]) {
            Concepto::query()->create([
                'nombre' => $nombre,
                'activo' => true,
            ]);
        }
    }
}
