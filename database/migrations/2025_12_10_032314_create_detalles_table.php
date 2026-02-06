<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requisicion_id')
                ->constrained('requisicions')
                ->cascadeOnDelete();

            $table->foreignId('sucursal_id')->nullable()->constrained('sucursals');

            $table->decimal('cantidad', 12, 2)->default(1);
            $table->string('descripcion', 255);

            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->boolean('genera_iva')->default(true);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('iva', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles');
    }
};
