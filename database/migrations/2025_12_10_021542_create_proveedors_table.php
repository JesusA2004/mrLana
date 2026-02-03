<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    // Ejecuta las migraciones.
    public function up(): void {
        Schema::create('proveedors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_duenio_id')
                ->constrained('users')
                ->cascadeOnDelete();
            // 1) Razón social / Beneficiario
            $table->string('razon_social', 200);
            // 2) RFC
            $table->string('rfc', 20);
            // 3) CLABE interbancaria
            $table->string('clabe', 30);
            // 4) Banco
            $table->string('banco', 120);
            // 5) Estatus del proveedor
            $table->string('status',15);
            $table->timestamps();
            // Evita duplicados de proveedor por dueño (razón social)
            $table->unique(
                ['user_duenio_id', 'razon_social'],
                'proveedors_user_razon_unique'
            );
            // Opcional pero útil: evita repetir RFC por dueño 
            $table->unique(
                ['user_duenio_id', 'rfc'],
                'proveedors_user_rfc_unique'
            );
            // Opcional pero útil: evita repetir CLABE por dueño
            $table->unique(
                ['user_duenio_id', 'clabe'],
                'proveedors_user_clabe_unique'
            );
        });
    }

    // Revierte las migraciones.
    public function down(): void {
        Schema::dropIfExists('proveedors');
    }

};
