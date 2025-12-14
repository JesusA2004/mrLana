<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CorporativoSeeder::class,
            SucursalSeeder::class,
            AreaSeeder::class,
            EmpleadoSeeder::class,
            UserSeeder::class,

            ConceptoSeeder::class,
            ProveedorSeeder::class,

            InversionistaSeeder::class,
            ContratoSeeder::class,
            PagoSeeder::class,

            RequisicionSeeder::class,
            DetalleSeeder::class,
            ComprobanteSeeder::class,
            FolioSeeder::class,
            AjusteSeeder::class,

            IngresoSeeder::class,
            GastoSeeder::class,
        ]);
    }
}
