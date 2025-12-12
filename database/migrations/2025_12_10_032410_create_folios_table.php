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
        Schema::create('folios', function (Blueprint $table) {
            $table->id();$table->string('folio', 100);
            $table->string('rfc_emisor', 20)->nullable();
            $table->string('rfc_receptor', 20)->nullable();
            $table->decimal('monto_total', 15, 2)->nullable();
            $table->enum('origen', ['SISTEMA','MANUAL'])->default('MANUAL');
            $table->foreignId('user_registro_id')
                ->constrained('users');
            $table->timestamps();
            $table->unique('folio');
        });
    }

    /**
     * Revierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('folios');
    }
};
