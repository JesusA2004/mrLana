<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RequisicionController;
use App\Http\Controllers\CorporativoController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\SucursalController;
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

    Route::resource('requisiciones', RequisicionController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('corporativos', CorporativoController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::resource('sucursales', SucursalController::class)
        ->parameters(['sucursales' => 'sucursal'])
        ->only(['index', 'store', 'update', 'destroy']);

    Route::post('/sucursales/bulk-destroy', [SucursalController::class, 'bulkDestroy'])
    ->name('sucursales.bulkDestroy');    

    // Ruta para subir el logo de un corporativo
    Route::post('corporativos/logo', [CorporativoController::class, 'uploadLogo'])
    ->name('corporativos.logo');

    // Ruta para ver los logs del sistema
    Route::get('/system-logs', [SystemLogController::class, 'index'])
            ->name('systemlogs.index');

});

require __DIR__.'/auth.php';
