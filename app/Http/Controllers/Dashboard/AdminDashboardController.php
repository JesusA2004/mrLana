<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller {

    public function index(): Response {
        $user = auth()->user();

        $tz = config('app.timezone', 'America/Mexico_City');
        $today = Carbon::now($tz);
        $start30 = $today->copy()->subDays(29)->startOfDay();
        $startMonth = $today->copy()->startOfMonth();
        $endMonth = $today->copy()->endOfMonth();

        // KPIs reales
        $corporativosActivos = DB::table('corporativos')->where('activo', 1)->count();
        $sucursalesActivas = DB::table('sucursals')->where('activo', 1)->count();
        $empleadosActivos = DB::table('empleados')->where('activo', 1)->count();

        $montoMes = (float) DB::table('requisicions')
            ->whereBetween('fecha_captura', [$startMonth, $endMonth])
            ->sum('monto_total');

        // Serie 30 días: monto + volumen
        $rows = DB::table('requisicions')
            ->selectRaw("DATE(fecha_captura) as d, SUM(monto_total) as monto, COUNT(*) as qty")
            ->where('fecha_captura', '>=', $start30)
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $map = $rows->keyBy('d');

        $trend30 = [];
        for ($i = 0; $i < 30; $i++) {
            $day = $start30->copy()->addDays($i)->toDateString();
            $trend30[] = [
                'name' => Carbon::parse($day, $tz)->format('d M'),
                'value' => (float) (($map[$day]->monto ?? 0)),
                'value2' => (int) (($map[$day]->qty ?? 0)),
            ];
        }

        // Quick links (tu UI ya los renderiza)
        $quickLinks = [
            ['label' => 'Requisiciones', 'routeName' => 'requisiciones.index', 'description' => 'Control del flujo completo.'],
            ['label' => 'Corporativos', 'routeName' => 'corporativos.index', 'description' => 'Gobierno organizacional.'],
            ['label' => 'Sucursales', 'routeName' => 'sucursales.index', 'description' => 'Estructura operativa.'],
            ['label' => 'System Log', 'routeName' => 'systemlogs.index', 'description' => 'Auditoría y trazabilidad.'],
        ];

        $kpis = [
            ['label' => 'Corporativos activos', 'value' => number_format($corporativosActivos), 'hint' => 'Base operativa vigente.'],
            ['label' => 'Sucursales activas', 'value' => number_format($sucursalesActivas), 'hint' => 'Cobertura actual.'],
            ['label' => 'Empleados activos', 'value' => number_format($empleadosActivos), 'hint' => 'Usuarios operando.'],
            ['label' => 'Monto del mes', 'value' => '$ ' . number_format($montoMes, 2), 'hint' => 'Total capturado en el periodo.'],
        ];

        return Inertia::render('Dashboard/AdminDashboard', [
            'dashboard' => [
                'headline' => 'Panel ejecutivo',
                'subheadline' => 'Visión global del negocio en tiempo real.',
                'userName' => $user->name,
                'userRole' => $user->rol,
                'kpis' => $kpis,
                'trend30' => $trend30,
                'financeLine' => [], // admin no lo necesita, pero no estorba
                'quickLinks' => $quickLinks,
            ],
        ]);
    }

}
