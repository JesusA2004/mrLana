<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Ejecuta las migraciones.
     */
    public function up(): void {
        Schema::create('proveedors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_duenio_id')
                ->constrained('users');
            $table->string('nombre_comercial', 200);
            $table->string('rfc', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('beneficiario', 200)->nullable();
            $table->string('banco', 120)->nullable();
            $table->string('cuenta', 50)->nullable();
            $table->string('clabe', 30)->nullable();
            $table->string('estatus', 20)->nullable();
            $table->timestamps();
            $table->unique(
                ['user_duenio_id', 'nombre_comercial'],
                'proveedors_user_nombre_unique'
            );
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void {
        Schema::dropIfExists('proveedors');
    }

};
