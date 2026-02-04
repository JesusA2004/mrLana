<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            // Relación con la requisición a la que pertenece
            $table->foreignId('requisicion_id')->constrained('requisicions');
            // Tipo de documento cargado
            $table->enum('tipo_doc', ['FACTURA','TICKET','NOTA','OTRO'])->default('FACTURA');
            // Montos del comprobante (para conciliación)
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            // Usuario que carga el comprobante
            $table->foreignId('user_carga_id')->constrained('users');
            $table->timestamps();
            // Índice para consultas rápidas por requisición
            $table->index(['requisicion_id'], 'comprobantes_requisicion_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('comprobantes');
    }

};
