<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller {

    public function index(): RedirectResponse {
        $rol = auth()->user()->rol ?? 'COLABORADOR';

        return match ($rol) {
            'ADMIN' => redirect()->route('dashboard.admin'),
            'CONTADOR' => redirect()->route('dashboard.contador'),
            default => redirect()->route('dashboard.colaborador'),
        };
    }

}
