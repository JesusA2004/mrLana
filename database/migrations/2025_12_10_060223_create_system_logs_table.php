<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración
     */
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            // Usuario que hizo la acción (opcional)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // Acción realizada: CREACION, ACTUALIZACION, ELIMINACION, LOGIN, etc.
            $table->string('accion', 50);
            // Nombre de la tabla afectada
            $table->string('tabla', 120);
            // ID del registro afectado dentro de esa tabla
            $table->unsignedBigInteger('registro_id')->nullable();
            // IP desde donde se ejecutó
            $table->string('ip_address', 45)->nullable();
            // Agente de usuario / navegador / cliente
            $table->string('user_agent', 255)->nullable();
            // Descripción armada en texto plano
            $table->text('descripcion')->nullable();
            // Fecha y hora del evento
            $table->timestamps();
        });
    }

    /**
     * Revierte los cambios realizados por la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
