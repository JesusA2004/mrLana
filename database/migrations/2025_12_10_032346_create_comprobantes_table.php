<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')
                ->constrained('requisicions')
                ->cascadeOnDelete();
            $table->enum('tipo_doc', ['FACTURA','TICKET','NOTA','OTRO'])->default('FACTURA');
            // Lo que tu vista muestra
            $table->date('fecha_emision')->nullable();
            $table->decimal('monto', 15, 2)->default(0);
            // Archivo para la columna "Archivo"
            $table->string('archivo_path', 255)->nullable();
            $table->string('archivo_original', 255)->nullable();
            // Revisión por comprobante (lo que pediste)
            $table->enum('estatus', ['PENDIENTE','APROBADO','RECHAZADO'])->default('PENDIENTE');
            $table->text('comentario_revision')->nullable();
            $table->foreignId('user_revision_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_at')->nullable();
            $table->foreignId('user_carga_id')->constrained('users');
            $table->timestamps();
            $table->index(['requisicion_id'], 'comprobantes_requisicion_idx');
            $table->index(['requisicion_id', 'estatus'], 'comprobantes_req_estatus_idx');
        });
    }

    public function down(): void {
        Schema::dropIfExists('comprobantes');
    }

};
