<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_gasto');
            $table->foreignId('corporativo_id')
                ->nullable()
                ->constrained('corporativos');
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursals');
            $table->foreignId('empleado_id')
                ->nullable()
                ->constrained('empleados');
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedors');
            $table->decimal('monto', 15, 2);
            $table->string('moneda', 10)->default('MXN');
            $table->enum('tipo_gasto', ['OPERATIVO','RUTA','VIAJE','CAJA_CHICA','OTRO'])
                  ->default('OTRO');
            $table->enum('metodo_pago', [
                'EFECTIVO','TRANSFERENCIA','TARJETA_CREDITO',
                'TARJETA_DEBITO','CHEQUE','OTRO'
            ])->default('TRANSFERENCIA');
            $table->enum('estatus_validacion', ['PENDIENTE','VALIDADO','RECHAZADO'])
                  ->default('PENDIENTE');
            $table->foreignId('requisicion_id')
                ->nullable()
                ->constrained('requisicions');
            $table->foreignId('comprobante_id')
                ->nullable()
                ->constrained('comprobantes');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
