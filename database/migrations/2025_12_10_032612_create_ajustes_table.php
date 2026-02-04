<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')->constrained('requisicions');
            $table->enum('tipo', ['DEVOLUCION','FALTANTE','INCREMENTO_AUTORIZADO']);
            $table->enum('sentido', ['A_FAVOR_EMPRESA','A_FAVOR_SOLICITANTE']);
            $table->decimal('monto', 15, 2);
            // Auditoría del incremento (cuando se autoriza subir monto de la requisición)
            $table->decimal('monto_anterior', 15, 2)->nullable();
            $table->decimal('monto_nuevo', 15, 2)->nullable();
            $table->enum('estatus', ['PENDIENTE','APROBADO','RECHAZADO','APLICADO','CANCELADO'])->default('PENDIENTE');
            $table->enum('metodo', ['TRANSFERENCIA','EFECTIVO','DESCUENTO_NOMINA','OTRO'])->nullable();
            $table->string('referencia', 120)->nullable();
            $table->string('motivo', 255)->nullable();
            $table->dateTime('fecha_registro');
            $table->dateTime('fecha_resolucion')->nullable();
            $table->foreignId('user_registro_id')->constrained('users');
            $table->foreignId('user_resuelve_id')->nullable()->constrained('users');
            $table->string('notas', 255)->nullable();
            $table->timestamps();
            // Índice combinado para eficiencia en consultas
            $table->index(['requisicion_id', 'tipo', 'estatus'], 'ajustes_requis_tipo_estatus_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('ajustes');
    }

};
