<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Ejecuta la migración.
     */
    public function up(): void {
        Schema::create('plantillas', function (Blueprint $table) {
            $table->id();
            // Usuario propietario de la plantilla
            $table->foreignId('user_id')->constrained('users');
            // Nombre descriptivo de la plantilla
            $table->string('nombre', 100)->nullable();
            // Campos opcionales que reflejan la cabecera de una requisición
            $table->foreignId('solicitante_id')->nullable()->constrained('empleados');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursals');
            $table->foreignId('comprador_corp_id')->nullable()->constrained('corporativos');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors');
            $table->foreignId('concepto_id')->nullable()->constrained('conceptos');
            $table->decimal('monto_subtotal', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);
            // Fechas opcionales para reflejar cuándo se capturó/aceptó la plantilla
            $table->dateTime('fecha_solicitud')->nullable();
            $table->dateTime('fecha_autorizacion')->nullable();
            $table->text('observaciones')->nullable();
            // Estado simple de la plantilla (BORRADOR o ELIMINADA)
            $table->enum('status', [
                'BORRADOR',
                'ELIMINADA',
            ])->default('BORRADOR');
            $table->timestamps();
            $table->index(['user_id', 'nombre'], 'plantillas_user_nombre_idx');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void {
        Schema::dropIfExists('plantillas');
    }

};
