<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- corporativos ---
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

        // --- sucursals ---
        Schema::create('sucursals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('corporativo_id')
        ->constrained('corporativos');
        $table->string('nombre', 150);
        $table->string('codigo', 20)->nullable();
        $table->string('ciudad', 120)->nullable();
        $table->string('estado', 120)->nullable();
        $table->string('direccion', 255)->nullable();
        $table->boolean('activo')->default(true);
        $table->timestamps();
        });

        // --- areas ---
        Schema::create('areas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('corporativo_id')
        ->nullable()
        ->constrained('corporativos');
        $table->string('nombre', 150);
        $table->boolean('activo')->default(true);
        $table->timestamps();
        });

        // --- empleados ---
        Schema::create('empleados', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sucursal_id')
        ->constrained('sucursals');
        $table->foreignId('area_id')
        ->nullable()
        ->constrained('areas');
        $table->string('nombre', 120);
        $table->string('apellido_paterno', 120);
        $table->string('apellido_materno', 120)->nullable();
        $table->string('email', 150)->nullable();
        $table->string('telefono', 30)->nullable();
        $table->string('puesto', 120)->nullable();
        $table->boolean('activo')->default(true);
        $table->timestamps();
        });

        // --- users ---
        Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->foreignId('empleado_id')
        ->nullable()
        ->constrained('empleados');
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->enum('rol', ['ADMIN', 'CONTADOR', 'COLABORADOR']);
        $table->boolean('activo')->default(true);
        $table->rememberToken();
        $table->timestamps();
        });

        // --- conceptos ---
        Schema::create('conceptos', function (Blueprint $table) {
        $table->id();
        $table->string('grupo', 150);
        $table->string('nombre', 150);
        $table->boolean('activo')->default(true);
        $table->timestamps();
        });

        // --- proveedors ---
        Schema::create('proveedors', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_duenio_id')
        ->constrained('users');
        $table->string('nombre_comercial', 200);
        $table->string('razon_social', 200)->nullable();
        $table->string('rfc', 20)->nullable();
        $table->string('direccion', 255)->nullable();
        $table->string('contacto', 150)->nullable();
        $table->string('telefono', 30)->nullable();
        $table->string('email', 150)->nullable();
        $table->string('beneficiario', 200)->nullable();
        $table->string('banco', 120)->nullable();
        $table->string('cuenta', 50)->nullable();
        $table->string('clabe', 30)->nullable();
        $table->timestamps();
        $table->unique(
        ['user_duenio_id', 'nombre_comercial'],
        'proveedors_user_nombre_unique'
        );
        });

        // --- inversionistas ---
        Schema::create('inversionistas', function (Blueprint $table) {
        $table->id();
        $table->string('nombre', 200);
        $table->string('rfc', 20)->nullable();
        $table->string('direccion', 255)->nullable();
        $table->string('telefono', 30)->nullable();
        $table->string('email', 150)->nullable();
        $table->timestamps();
        });

        // --- contratos ---
        Schema::create('contratos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('inversionista_id')
        ->constrained('inversionistas');
        $table->foreignId('corporativo_id')
        ->nullable()
        ->constrained('corporativos');
        $table->string('no_contrato', 50)->unique();
        $table->date('fecha_contrato');
        $table->decimal('capital_inicial', 15, 2);
        $table->date('fecha_reembolso');
        $table->integer('plazo_meses');
        $table->decimal('tasa_anual', 8, 5);
        $table->decimal('tasa_mensual', 8, 5);
        $table->string('banco', 120);
        $table->string('clabe', 30);
        $table->string('cuenta', 30);
        $table->decimal('rendimiento_bruto_mensual', 15, 2);
        $table->decimal('retencion_mensual', 15, 2);
        $table->decimal('rendimiento_neto_mensual', 15, 2);
        $table->enum('periodicidad_pago', ['mensual', 'quincenal', 'semanal', 'unico'])
        ->default('mensual');
        $table->unsignedTinyInteger('dia_pago');
        $table->enum('status', ['CAPTURADA', 'ACTIVA', 'VENCIDA', 'CANCELADA'])
        ->default('CAPTURADA');
        $table->timestamps();
        });

        // --- pagos ---
        Schema::create('pagos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('contrato_id')
        ->constrained('contratos');
        $table->date('fecha_pago');
        $table->decimal('rendimiento_bruto', 15, 2);
        $table->decimal('retenciones', 15, 2);
        $table->decimal('rendimiento_neto', 15, 2);
        $table->enum('status', ['PENDIENTE', 'PAGADO', 'CANCELADO'])
        ->default('PENDIENTE');
        $table->string('recibo_pago_ruta', 255)->nullable();
        $table->timestamps();
        });

        // --- requisicions ---
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

        // --- detalles ---
        Schema::create('detalles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('requisicion_id')
        ->constrained('requisicions');
        $table->foreignId('sucursal_id')
        ->nullable()
        ->constrained('sucursals');
        $table->decimal('cantidad', 12, 2)->default(1);
        $table->string('descripcion', 255);
        $table->decimal('precio_unitario', 15, 2)->default(0);
        $table->decimal('subtotal', 15, 2)->default(0);
        $table->decimal('iva', 15, 2)->default(0);
        $table->decimal('total', 15, 2)->default(0);
        $table->timestamps();
        });

        // --- comprobantes ---
        Schema::create('comprobantes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('requisicion_id')
        ->constrained('requisicions');
        $table->foreignId('proveedor_id')
        ->nullable()
        ->constrained('proveedors');
        $table->enum('tipo_doc', ['FACTURA','TICKET','NOTA','OTRO'])
        ->default('FACTURA');
        $table->string('uuid_cfdi', 50)->nullable();
        $table->string('folio', 50)->nullable();
        $table->string('rfc_emisor', 20)->nullable();
        $table->string('rfc_receptor', 20)->nullable();
        $table->decimal('subtotal', 15, 2)->default(0);
        $table->decimal('iva', 15, 2)->default(0);
        $table->decimal('total', 15, 2)->default(0);
        $table->enum('estatus', ['CARGADO','EN_REVISION','VALIDADO','RECHAZADO'])
        ->default('CARGADO');
        $table->date('fecha_emision')->nullable();
        $table->dateTime('fecha_carga');
        $table->foreignId('user_carga_id')
        ->constrained('users');
        $table->timestamps();
        });

        // --- folios ---
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

        // --- ajustes ---
        Schema::create('ajustes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('requisicion_id')
        ->constrained('requisicions');
        $table->enum('tipo', ['DEVOLUCION','FALTANTE']);
        $table->decimal('monto', 15, 2);
        $table->enum('estatus', ['PENDIENTE','APLICADO'])
        ->default('PENDIENTE');
        $table->dateTime('fecha_registro');
        $table->foreignId('user_registro_id')
        ->constrained('users');
        $table->string('notas', 255)->nullable();
        $table->timestamps();
        });

        // --- ingresos ---
        Schema::create('ingresos', function (Blueprint $table) {
        $table->id();
        $table->date('fecha_ingreso');
        $table->foreignId('corporativo_id')
        ->nullable()
        ->constrained('corporativos');
        $table->foreignId('sucursal_id')
        ->nullable()
        ->constrained('sucursals');
        $table->foreignId('inversionista_id')
        ->nullable()
        ->constrained('inversionistas');
        $table->decimal('monto', 15, 2);
        $table->string('moneda', 10)->default('MXN');
        $table->enum('origen', ['VENTA','APORTE_INVERSIONISTA','OTRO'])
        ->default('OTRO');
        $table->string('descripcion', 255)->nullable();
        $table->timestamps();
        });

        // --- gastos ---
        Schema::create('gastos', function (Blueprint $table) {
        $table->id();
        $table->date('fecha_gasto');
        $table->foreignId('corporativo_id')
        ->nullable()
        ->constrained('corporativos');
        $table->foreignId('sucursal_id')
        ->nullable()
        ->constrained('sucursals');
        $table->foreignId('empleado_id')
        ->nullable()
        ->constrained('empleados');
        $table->foreignId('proveedor_id')
        ->nullable()
        ->constrained('proveedors');
        $table->decimal('monto', 15, 2);
        $table->string('moneda', 10)->default('MXN');
        $table->enum('tipo_gasto', ['OPERATIVO','RUTA','VIAJE','CAJA_CHICA','OTRO'])
        ->default('OTRO');
        $table->enum('metodo_pago', [
        'EFECTIVO','TRANSFERENCIA','TARJETA_CREDITO',
        'TARJETA_DEBITO','CHEQUE','OTRO'
        ])->default('TRANSFERENCIA');
        $table->enum('estatus_validacion', ['PENDIENTE','VALIDADO','RECHAZADO'])
        ->default('PENDIENTE');
        $table->foreignId('requisicion_id')
        ->nullable()
        ->constrained('requisicions');
        $table->foreignId('comprobante_id')
        ->nullable()
        ->constrained('comprobantes');
        $table->string('descripcion', 255)->nullable();
        $table->timestamps();
        });

        // --- system_logs ---
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

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
        Schema::dropIfExists('gastos');
        Schema::dropIfExists('ingresos');
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('folios');
        Schema::dropIfExists('comprobantes');
        Schema::dropIfExists('detalles');
        Schema::dropIfExists('requisicions');
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('contratos');
        Schema::dropIfExists('inversionistas');
        Schema::dropIfExists('proveedors');
        Schema::dropIfExists('conceptos');
        Schema::dropIfExists('users');
        Schema::dropIfExists('empleados');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('sucursals');
        Schema::dropIfExists('corporativos');
    }
};
