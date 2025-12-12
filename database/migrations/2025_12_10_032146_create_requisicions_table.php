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
        Schema::create('requisicions', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 50)->unique();
            $table->enum('tipo', ['ANTICIPO', 'REEMBOLSO']);
            $table->enum('status', ['BORRADOR', 'CAPTURADA', 'COMPROBADA', 'PAGADA', 'CANCELADA'])
                  ->default('BORRADOR');
            $table->foreignId('comprador_corp_id')
                ->constrained('corporativos');
            $table->foreignId('sucursal_id')
                ->constrained('sucursals');
            $table->foreignId('solicitante_id')
                ->constrained('empleados');
            $table->foreignId('proveedor_id')
                ->nullable()
                ->constrained('proveedors');
            $table->foreignId('concepto_id')
                ->constrained('conceptos');
            $table->decimal('monto_subtotal', 15, 2)->default(0);
            $table->decimal('monto_iva', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);
            $table->string('lugar_entrega_texto', 200)->nullable();
            $table->date('fecha_entrega')->nullable();
            $table->dateTime('fecha_captura');
            $table->date('fecha_pago')->nullable();
            $table->string('beneficiario_pago', 200)->nullable();
            $table->string('banco_pago', 120)->nullable();
            $table->string('clabe_pago', 30)->nullable();
            $table->string('cuenta_pago', 30)->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('creada_por_user_id')
                ->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Rvierte la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisicions');
    }
};
