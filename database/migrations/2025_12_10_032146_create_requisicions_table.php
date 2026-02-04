<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Ejecuta la migración.
     */
    public function up(): void {
        Schema::create('requisicions', function (Blueprint $table) {
            $table->id();
            // Folio único asignado a cada requisición
            $table->string('folio', 50)->unique();
            // Estado de la requisición.
            $table->enum('status', [
                'BORRADOR',
                'ELIMINADA',
                'CAPTURADA',
                'PAGO_AUTORIZADO',
                'PAGO_RECHAZADO',
                'PAGADA',
                'POR_COMPROBAR',
                'COMPROBACION_ACEPTADA',
                'COMPROBACION_RECHAZADA',
            ])->default('BORRADOR');
            // Relaciones con catálogos básicos
            $table->foreignId('solicitante_id')->constrained('empleados');
            $table->foreignId('sucursal_id')->constrained('sucursals');
            $table->foreignId('comprador_corp_id')->constrained('corporativos');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors');
            $table->foreignId('concepto_id')->constrained('conceptos');
            // Totales de la requisición
            $table->decimal('monto_subtotal', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);
            // Fecha en la que el solicitante crea o envía la requisición
            $table->dateTime('fecha_solicitud');
            // Fecha en la que el contador o administrador autoriza la requisición
            $table->dateTime('fecha_autorizacion')->nullable();
            // Fecha en la que se realiza el pago (si aplica)
            $table->date('fecha_pago')->nullable();
            // Observaciones libres
            $table->text('observaciones')->nullable();
            // Usuario que creó la requisición (para auditoría)
            $table->foreignId('creada_por_user_id')->constrained('users');
            $table->timestamps();
            // Índices para acelerar consultas comunes
            $table->index(['status', 'sucursal_id', 'fecha_solicitud'], 'requis_status_sucursal_fecha_idx');
            $table->index(['proveedor_id', 'fecha_pago'], 'requis_proveedor_pago_idx');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void {
        Schema::dropIfExists('requisicions');
    }

};
