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
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisicion_id')
                ->constrained('requisicions');
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedors');
            $table->enum('tipo_doc', ['FACTURA','TICKET','NOTA','OTRO'])
                  ->default('FACTURA');
            $table->string('uuid_cfdi', 50)->nullable();
            $table->string('folio', 50)->nullable();
            $table->string('rfc_emisor', 20)->nullable();
            $table->string('rfc_receptor', 20)->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('estatus', ['CARGADO','EN_REVISION','VALIDADO','RECHAZADO'])
                  ->default('CARGADO');
            $table->date('fecha_emision')->nullable();
            $table->dateTime('fecha_carga');
            $table->foreignId('user_carga_id')
                ->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
