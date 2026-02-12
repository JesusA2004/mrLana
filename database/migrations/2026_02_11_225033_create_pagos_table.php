<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requisicion_id')
                ->constrained('requisicions')
                ->cascadeOnDelete();

            // Snapshot del beneficiario al momento del pago (para auditoría)
            $table->string('beneficiario_nombre', 200);
            $table->string('cuenta', 40)->nullable();
            $table->string('clabe', 30)->nullable();
            $table->string('banco', 120)->nullable();

            $table->enum('tipo_pago', ['TRANSFERENCIA','EFECTIVO','TARJETA','CHEQUE','OTRO'])->default('TRANSFERENCIA');

            $table->decimal('monto', 15, 2);
            $table->date('fecha_pago');

            // Archivo comprobante del pago
            $table->string('archivo_path', 255)->nullable();
            $table->string('archivo_original', 255)->nullable();
            $table->string('mime', 80)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->string('referencia', 140)->nullable();

            // Usuario que sube el comprobante de pago
            $table->foreignId('user_carga_id')->constrained('users');

            $table->timestamps();

            $table->index(['requisicion_id', 'fecha_pago'], 'pagos_req_fecha_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
