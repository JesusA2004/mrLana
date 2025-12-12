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
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')
                ->constrained('requisicions');
            $table->enum('tipo', ['DEVOLUCION','FALTANTE']);
            $table->decimal('monto', 15, 2);
            $table->enum('estatus', ['PENDIENTE','APLICADO'])
                  ->default('PENDIENTE');
            $table->dateTime('fecha_registro');
            $table->foreignId('user_registro_id')
                ->constrained('users');
            $table->string('notas', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajustes');
    }
};
