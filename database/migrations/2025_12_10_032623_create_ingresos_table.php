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
        Schema::create('ingresos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_ingreso');
            $table->foreignId('corporativo_id')
                ->nullable()
                ->constrained('corporativos');
            $table->foreignId('sucursal_id')
                ->nullable()
                ->constrained('sucursals');
            $table->foreignId('inversionista_id')
                ->nullable()
                ->constrained('inversionistas');
            $table->decimal('monto', 15, 2);
            $table->string('moneda', 10)->default('MXN');
            $table->enum('origen', ['VENTA','APORTE_INVERSIONISTA','OTRO'])
                  ->default('OTRO');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingresos');
    }
};
