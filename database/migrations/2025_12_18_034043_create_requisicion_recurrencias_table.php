<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     */
    public function up(): void
    {
        Schema::create('requisicion_recurrencias', function (Blueprint $table) {
            $table->id();

            // Gobernanza de plantilla
            $table->enum('status', ['PENDIENTE_APROBACION', 'APROBADA', 'RECHAZADA'])
                ->default('PENDIENTE_APROBACION');
            $table->boolean('activo')->default(true);

            // Frecuencia
            $table->enum('frecuencia', ['SEMANAL', 'QUINCENAL', 'MENSUAL', 'BIMESTRAL', 'TRIMESTRAL', 'ANUAL']);
            $table->unsignedSmallInteger('intervalo')->default(1);

            $table->unsignedTinyInteger('dia_semana')->nullable(); // 1-7 (si aplica)
            $table->unsignedTinyInteger('dia_mes')->nullable();    // 1-31 (si aplica)
            $table->time('hora_ejecucion')->nullable();

            // Scheduler control
            $table->dateTime('proxima_ejecucion')->nullable();
            $table->dateTime('ultima_generacion')->nullable();

            // Plantilla base
            $table->enum('tipo', ['ANTICIPO', 'REEMBOLSO']);

            $table->foreignId('solicitante_id')->constrained('empleados');
            $table->foreignId('sucursal_id')->constrained('sucursals'); // sucursal que absorbe el gasto
            $table->foreignId('comprador_corp_id')->constrained('corporativos');

            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors');
            $table->foreignId('concepto_id')->constrained('conceptos');

            $table->decimal('monto_subtotal', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);

            $table->text('observaciones')->nullable();

            // Auditoría
            $table->foreignId('creada_por_user_id')->constrained('users');
            $table->foreignId('aprobada_por_user_id')->nullable()->constrained('users');
            $table->dateTime('fecha_aprobacion')->nullable();

            $table->timestamps();

            $table->index(['activo', 'status', 'proxima_ejecucion'], 'recurrencias_run_idx');
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisicion_recurrencias');
    }
};
