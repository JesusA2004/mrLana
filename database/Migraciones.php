<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =========================
        // CORE ORGANIZACIONAL
        // =========================

        Schema::create('corporativos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('rfc', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('codigo', 20)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('sucursals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporativo_id')->constrained('corporativos');
            $table->string('nombre', 150);
            $table->string('codigo', 20)->nullable();
            $table->string('ciudad', 120)->nullable();
            $table->string('estado', 120)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporativo_id')->nullable()->constrained('corporativos');
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursals');
            $table->foreignId('area_id')->nullable()->constrained('areas');

            $table->string('nombre', 120);
            $table->string('apellido_paterno', 120);
            $table->string('apellido_materno', 120)->nullable();

            $table->string('email', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('puesto', 120)->nullable();

            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados');

            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();

            $table->string('password');
            $table->enum('rol', ['ADMIN', 'CONTADOR', 'COLABORADOR']);
            $table->boolean('activo')->default(true);

            $table->rememberToken();
            $table->timestamps();
        });

        // =========================
        // CATÁLOGOS
        // =========================

        Schema::create('conceptos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('proveedors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_duenio_id')->constrained('users');

            $table->string('nombre_comercial', 200);
            $table->string('razon_social', 200)->nullable();
            $table->string('rfc', 20)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('contacto', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();

            // Depósito (empleado o institución)
            $table->string('beneficiario', 200)->nullable();
            $table->string('banco', 120)->nullable();
            $table->string('cuenta', 50)->nullable();
            $table->string('clabe', 30)->nullable();

            $table->timestamps();

            $table->unique(['user_duenio_id', 'nombre_comercial'], 'proveedors_user_nombre_unique');
        });

        // =========================
        // RECURRENCIAS (PAGOS RECURRENTES)
        // =========================

        Schema::create('requisicion_recurrencias', function (Blueprint $table) {
            $table->id();

            // Se aprueba plantilla (no el documento)
            $table->enum('status', ['PENDIENTE_APROBACION', 'APROBADA', 'RECHAZADA'])
                ->default('PENDIENTE_APROBACION');

            $table->boolean('activo')->default(true);

            $table->enum('frecuencia', ['SEMANAL', 'QUINCENAL', 'MENSUAL', 'BIMESTRAL', 'TRIMESTRAL', 'ANUAL']);
            $table->unsignedSmallInteger('intervalo')->default(1);

            $table->unsignedTinyInteger('dia_semana')->nullable(); // 1-7 si aplica
            $table->unsignedTinyInteger('dia_mes')->nullable();    // 1-31 si aplica
            $table->time('hora_ejecucion')->nullable();

            $table->dateTime('proxima_ejecucion')->nullable();
            $table->dateTime('ultima_generacion')->nullable();

            // Plantilla base
            $table->enum('tipo', ['ANTICIPO', 'REEMBOLSO']);

            $table->foreignId('solicitante_id')->constrained('empleados');
            $table->foreignId('sucursal_id')->constrained('sucursals'); // sucursal que absorbe
            $table->foreignId('comprador_corp_id')->constrained('corporativos');

            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors');
            $table->foreignId('concepto_id')->constrained('conceptos');

            $table->decimal('monto_subtotal', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);

            $table->text('observaciones')->nullable();

            // Auditoría
            $table->foreignId('creada_por_user_id')->constrained('users');
            $table->foreignId('aprobada_por_user_id')->nullable()->constrained('users');
            $table->dateTime('fecha_aprobacion')->nullable();

            $table->timestamps();

            $table->index(['activo', 'status', 'proxima_ejecucion'], 'recurrencias_run_idx');
        });

        // =========================
        // REQUISICIONES
        // =========================

        Schema::create('requisicions', function (Blueprint $table) {
            $table->id();

            $table->string('folio', 50)->unique();

            $table->enum('tipo', ['ANTICIPO', 'REEMBOLSO']);

            // Estatus decide la requisición, no el comprobante
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
            $table->foreignId('sucursal_id')->constrained('sucursals'); // sucursal que absorbe
            $table->foreignId('comprador_corp_id')->constrained('corporativos');

            // A quién se paga (cuentas están en proveedors)
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedors');

            $table->foreignId('concepto_id')->constrained('conceptos');

            // Sin IVA, solo subtotal y total
            $table->decimal('monto_subtotal', 15, 2)->default(0);
            $table->decimal('monto_total', 15, 2)->default(0);

            $table->dateTime('fecha_captura');
            $table->date('fecha_pago')->nullable();

            $table->text('observaciones')->nullable();

            $table->foreignId('creada_por_user_id')->constrained('users');

            $table->timestamps();

            $table->index(['status', 'sucursal_id', 'fecha_captura'], 'requis_status_sucursal_fecha_idx');
            $table->index(['proveedor_id', 'fecha_pago'], 'requis_proveedor_pago_idx');
        });

        // =========================
        // DETALLES
        // =========================

        Schema::create('detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requisicion_id')->constrained('requisicions');
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursals');

            $table->decimal('cantidad', 12, 2)->default(1);
            $table->string('descripcion', 255);

            $table->decimal('precio_unitario', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->timestamps();
        });

        // =========================
        // COMPROBANTES (SIN PROVEEDOR, SIN VALIDACIÓN DE REQUISICIÓN)
        // =========================

        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requisicion_id')->constrained('requisicions');

            $table->enum('tipo_doc', ['FACTURA','TICKET','NOTA','OTRO'])->default('FACTURA');

            $table->string('folio', 50)->nullable();

            // Útil para conciliación; si no lo quieres, puedes quitarlo
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            $table->dateTime('fecha_carga');

            $table->foreignId('user_carga_id')->constrained('users');

            $table->timestamps();

            $table->index(['requisicion_id'], 'comprobantes_requisicion_idx');
        });

        // =========================
        // FOLIOS (SIMPLIFICADA: SIN RFCs, SIN ORIGEN)
        // =========================

        Schema::create('folios', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 100)->unique();
            $table->decimal('monto_total', 15, 2)->nullable();
            $table->foreignId('user_registro_id')->constrained('users');
            $table->timestamps();
        });

        // =========================
        // AJUSTES (MEJORADA)
        // =========================

        Schema::create('ajustes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requisicion_id')->constrained('requisicions');

            // Incluye incremento autorizado para subir monto tras comprobar gasto mayor
            $table->enum('tipo', ['DEVOLUCION', 'FALTANTE', 'INCREMENTO_AUTORIZADO']);
            $table->enum('sentido', ['A_FAVOR_EMPRESA', 'A_FAVOR_SOLICITANTE']);

            // Diferencia
            $table->decimal('monto', 15, 2);

            // Auditoría cuando se incrementa el monto autorizado
            $table->decimal('monto_anterior', 15, 2)->nullable();
            $table->decimal('monto_nuevo', 15, 2)->nullable();

            $table->enum('estatus', ['PENDIENTE', 'APROBADO', 'RECHAZADO', 'APLICADO', 'CANCELADO'])
                ->default('PENDIENTE');

            $table->enum('metodo', ['TRANSFERENCIA', 'EFECTIVO', 'DESCUENTO_NOMINA', 'OTRO'])->nullable();
            $table->string('referencia', 120)->nullable();
            $table->string('motivo', 255)->nullable();

            $table->dateTime('fecha_registro');
            $table->dateTime('fecha_resolucion')->nullable();

            $table->foreignId('user_registro_id')->constrained('users');
            $table->foreignId('user_resuelve_id')->nullable()->constrained('users');

            $table->string('notas', 255)->nullable();

            $table->timestamps();

            $table->index(['requisicion_id', 'tipo', 'estatus'], 'ajustes_requis_tipo_estatus_idx');
        });

        // =========================
        // AUDITORÍA
        // =========================

        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('accion', 50);
            $table->string('tabla', 120);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->text('descripcion')->nullable();

            $table->timestamps();

            $table->index(['tabla', 'registro_id'], 'logs_tabla_registro_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('folios');
        Schema::dropIfExists('comprobantes');
        Schema::dropIfExists('detalles');
        Schema::dropIfExists('requisicions');
        Schema::dropIfExists('requisicion_recurrencias');
        Schema::dropIfExists('proveedors');
        Schema::dropIfExists('conceptos');
        Schema::dropIfExists('users');
        Schema::dropIfExists('empleados');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('sucursals');
        Schema::dropIfExists('corporativos');
    }
    
};
