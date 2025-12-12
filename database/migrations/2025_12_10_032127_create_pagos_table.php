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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')
                ->constrained('contratos');
            $table->date('fecha_pago');
            $table->decimal('rendimiento_bruto', 15, 2);
            $table->decimal('retenciones', 15, 2);
            $table->decimal('rendimiento_neto', 15, 2);
            $table->enum('status', ['PENDIENTE', 'PAGADO', 'CANCELADO'])
                  ->default('PENDIENTE');
            $table->string('recibo_pago_ruta', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
