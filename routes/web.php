<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequisicionController;
use App\Http\Controllers\CorporativoController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\ConceptoController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // =========================
    // CRUDs base
    // =========================
    Route::resource('corporativos', CorporativoController::class)->only(['index','store','update','destroy']);
    Route::post('corporativos/logo', [CorporativoController::class, 'uploadLogo'])->name('corporativos.logo');

    Route::resource('sucursales', SucursalController::class)
        ->parameters(['sucursales' => 'sucursal'])
        ->only(['index','store','update','destroy']);
    Route::post('/sucursales/bulk-destroy', [SucursalController::class, 'bulkDestroy'])->name('sucursales.bulkDestroy');

    Route::resource('areas', AreaController::class)->only(['index','store','update','destroy']);
    Route::post('/areas/bulk-destroy', [AreaController::class, 'bulkDestroy'])->name('areas.bulkDestroy');

    Route::resource('empleados', EmpleadoController::class)->only(['index','store','update','destroy']);
    Route::post('/empleados/bulk-destroy', [EmpleadoController::class, 'bulkDestroy'])->name('empleados.bulkDestroy');

    Route::resource('conceptos', ConceptoController::class)->only(['index','store','update','destroy']);
    Route::post('/conceptos/bulk-destroy', [ConceptoController::class, 'bulkDestroy'])->name('conceptos.bulkDestroy');

    // =========================
    // Requisiciones (base + flujo)
    // =========================

    // Recurso principal
    Route::resource('requisiciones', RequisicionController::class)
        ->only(['index','store','update','destroy']);

    Route::post('/requisiciones/bulk-destroy', [RequisicionController::class, 'bulkDestroy'])
        ->name('requisiciones.bulkDestroy');

    // Vistas extra que tu UI necesita
    Route::get('/requisiciones/create', [RequisicionController::class, 'create'])
        ->name('requisiciones.create');

    Route::get('/requisiciones/{requisicion}', [RequisicionController::class, 'show'])
        ->name('requisiciones.show');

    // PDF / impresión (abre en nueva pestaña)
    Route::get('/requisiciones/{requisicion}/print', [RequisicionController::class, 'print'])
        ->name('requisiciones.print');

    // Flujo: pagar (solo CONTADOR)
    Route::get('/requisiciones/{requisicion}/pagar', [RequisicionController::class, 'pagar'])
        ->name('requisiciones.pagar');
    Route::post('/requisiciones/{requisicion}/pagar', [RequisicionController::class, 'storePago'])
        ->name('requisiciones.pagar.store');

    // Flujo: comprobar (COLABORADOR/CONTADOR/ADMIN)
    Route::get('/requisiciones/{requisicion}/comprobar', [RequisicionController::class, 'comprobar'])
        ->name('requisiciones.comprobar');
    Route::post('/requisiciones/{requisicion}/comprobantes', [RequisicionController::class, 'storeComprobante'])
        ->name('requisiciones.comprobantes.store');

    // Logs
    Route::get('/system-logs', [SystemLogController::class, 'index'])->name('systemlogs.index');
});

require __DIR__.'/auth.php';
