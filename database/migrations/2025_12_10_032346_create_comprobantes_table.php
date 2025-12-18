<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requisicion_id')
                ->constrained('requisicions');

            $table->enum('tipo_doc', ['FACTURA','TICKET','NOTA','OTRO'])
                ->default('FACTURA');

            // Folio interno del documento (si aplica)
            $table->string('folio', 50)->nullable();

            // Montos del comprobante (útiles para conciliación)
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->foreignId('user_carga_id')
                ->constrained('users');
            $table->timestamps();
            $table->index(['requisicion_id'], 'comprobantes_requisicion_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }

};
