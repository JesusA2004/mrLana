<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void {
        Schema::table('proveedors', function (Blueprint $table) {
            $table->dropUnique('proveedors_user_razon_unique');
        });
    }

    public function down(): void {
        Schema::table('proveedors', function (Blueprint $table) {
            $table->unique(
                ['user_duenio_id', 'razon_social'],
                'proveedors_user_razon_unique'
            );
        });
    }

};
