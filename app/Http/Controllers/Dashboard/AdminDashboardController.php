<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller {

    /**
     * Muestra el panel ejecutivo para el rol ADMIN.
     * Incluye KPIs de corporativos, sucursales, empleados y el monto total de requisiciones.
     * La serie de tendencia de 30 días se basa en la fecha de solicitud de las requisiciones.
     */
    public function index(): Response {
        $user = auth()->user();

        $tz = config('app.timezone', 'America/Mexico_City');
        $today = Carbon::now($tz);
        $start30 = $today->copy()->subDays(29)->startOfDay();

        // Conteo de entidades activas
        $corporativosActivos = DB::table('corporativos')->where('activo', 1)->count();
        $sucursalesActivas   = DB::table('sucursals')->where('activo', 1)->count();
        $empleadosActivos    = DB::table('empleados')->where('activo', 1)->count();

        // Monto total acumulado de requisiciones (sin filtro de fechas)
        $montoMes = (float) DB::table('requisicions')->sum('monto_total');

        // Serie de tendencia (últimos 30 días) basada en fecha de solicitud
        $rows = DB::table('requisicions')
            ->selectRaw("DATE(fecha_solicitud) as d, SUM(monto_total) as monto, COUNT(*) as qty")
            ->where('fecha_solicitud', '>=', $start30)
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $map = $rows->keyBy('d');

        $trend30 = [];
        for ($i = 0; $i < 30; $i++) {
            $day = $start30->copy()->addDays($i)->toDateString();
            $trend30[] = [
                'name'  => Carbon::parse($day, $tz)->format('d M'),
                'value' => (float) ($map[$day]->monto ?? 0), // Monto total del día
                'value2'=> (int)   ($map[$day]->qty  ?? 0), // Número de requisiciones del día
            ];
        }

        // Accesos rápidos del panel
        $quickLinks = [
            ['label' => 'Requisiciones', 'routeName' => 'requisiciones.index', 'description' => 'Control del flujo completo.'],
            ['label' => 'Corporativos',  'routeName' => 'corporativos.index',  'description' => 'Gobierno organizacional.'],
            ['label' => 'Sucursales',    'routeName' => 'sucursales.index',    'description' => 'Estructura operativa.'],
            ['label' => 'System Log',    'routeName' => 'systemlogs.index',    'description' => 'Auditoría y trazabilidad.'],
        ];

        $kpis = [
            ['label' => 'Corporativos activos', 'value' => number_format($corporativosActivos), 'hint' => 'Base operativa vigente.'],
            ['label' => 'Sucursales activas',   'value' => number_format($sucursalesActivas),   'hint' => 'Cobertura actual.'],
            ['label' => 'Empleados activos',    'value' => number_format($empleadosActivos),    'hint' => 'Usuarios operando.'],
            ['label' => 'Monto del mes',        'value' => '$ ' . number_format($montoMes, 2),  'hint' => 'Total capturado en el periodo.'],
        ];

        return Inertia::render('Dashboard/AdminDashboard', [
            'dashboard' => [
                'headline'    => 'Panel ejecutivo',
                'subheadline' => 'Visión global del negocio en tiempo real.',
                'userName'    => $user->name,
                'userRole'    => $user->rol,
                'kpis'        => $kpis,
                'trend30'     => $trend30,
                'financeLine' => [], // El admin no requiere serie financiera detallada
                'quickLinks'  => $quickLinks,
            ],
        ]);
    }

}
