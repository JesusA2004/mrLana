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
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inversionista_id')
                ->constrained('inversionistas');
            $table->foreignId('corporativo_id')
                ->nullable()
                ->constrained('corporativos');
            $table->string('no_contrato', 50)->unique();
            $table->date('fecha_contrato');
            $table->decimal('capital_inicial', 15, 2);
            $table->date('fecha_reembolso');
            $table->integer('plazo_meses');
            $table->decimal('tasa_anual', 8, 5);
            $table->decimal('tasa_mensual', 8, 5);
            $table->string('banco', 120);
            $table->string('clabe', 30);
            $table->string('cuenta', 30);
            $table->decimal('rendimiento_bruto_mensual', 15, 2);
            $table->decimal('retencion_mensual', 15, 2);
            $table->decimal('rendimiento_neto_mensual', 15, 2);
            $table->enum('periodicidad_pago', ['mensual', 'quincenal', 'semanal', 'unico'])
                  ->default('mensual');
            $table->unsignedTinyInteger('dia_pago');
            $table->enum('status', ['CAPTURADA', 'ACTIVA', 'VENCIDA', 'CANCELADA'])
                  ->default('CAPTURADA');
            $table->timestamps();
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
