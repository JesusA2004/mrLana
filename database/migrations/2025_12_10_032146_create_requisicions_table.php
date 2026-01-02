<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requisicions', function (Blueprint $table) {
            $table->id();

            $table->string('folio', 50)->unique();

            $table->enum('tipo', ['ANTICIPO', 'REEMBOLSO']);

            $table->enum('status', [
                'BORRADOR',
                'CAPTURADA',
                'PAGADA',
                'POR_COMPROBAR',
                'COMPROBADA',
                'ACEPTADA',
                'RECHAZADA',
            ])->default('BORRADOR');

            $table->foreignId('recurrencia_id')->nullable()->constrained('requisicion_recurrencias');

            $table->foreignId('solicitante_id')->constrained('empleados');
            $table->foreignId('sucursal_id')->constrained('sucursals');
            $table->foreignId('comprador_corp_id')->constrained('corporativos');

            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors');
            $table->foreignId('concepto_id')->constrained('conceptos');

            $table->decimal('monto_subtotal', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);

            $table->dateTime('fecha_captura');
            $table->date('fecha_pago')->nullable();

            $table->text('observaciones')->nullable();

            $table->foreignId('creada_por_user_id')->constrained('users');

            $table->timestamps();

            $table->index(['status','sucursal_id','fecha_captura'], 'requis_status_sucursal_fecha_idx');
            $table->index(['proveedor_id','fecha_pago'], 'requis_proveedor_pago_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requisicions');
    }
    
};
