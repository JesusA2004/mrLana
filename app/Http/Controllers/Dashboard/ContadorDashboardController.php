<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContadorDashboardController extends Controller {

    /**
     * Muestra el panel financiero para el rol CONTADOR.
     * Incluye KPIs de pagos y comprobaciones, serie de tendencia y línea financiera.
     */
    public function index(): Response {
        $user = auth()->user();

        $tz = config('app.timezone', 'America/Mexico_City');
        $today      = Carbon::now($tz);
        $start30    = $today->copy()->subDays(29)->startOfDay();
        $startMonth = $today->copy()->startOfMonth();
        $endMonth   = $today->copy()->endOfMonth();

        // Requisiciones pendientes de pago (CAPTURADA o PAGO_AUTORIZADO)
        $porPagar = DB::table('requisicions')
            ->whereIn('status', ['CAPTURADA', 'PAGO_AUTORIZADO'])
            ->count();

        // Requisiciones en estado POR_COMPROBAR
        $porComprobar = DB::table('requisicions')
            ->where('status', 'POR_COMPROBAR')
            ->count();

        // Requisiciones cuya comprobación ya fue revisada en el mes actual
        $comprobadasMes = DB::table('requisicions')
            ->whereIn('status', ['COMPROBACION_ACEPTADA', 'COMPROBACION_RECHAZADA'])
            ->whereBetween('fecha_solicitud', [$startMonth, $endMonth])
            ->count();

        // Monto total pagado en el mes (fecha_pago)
        $montoPagadoMes = (float) DB::table('requisicions')
            ->where('status', 'PAGADA')
            ->whereBetween('fecha_pago', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->sum('monto_total');

        // Serie financiera (últimos 30 días) basada en COALESCE(fecha_pago, fecha_solicitud)
        $rows = DB::table('requisicions')
            ->selectRaw("DATE(COALESCE(fecha_pago, fecha_solicitud)) as d, SUM(monto_total) as monto, COUNT(*) as qty")
            ->whereRaw("COALESCE(fecha_pago, fecha_solicitud) >= ?", [$start30])
            ->whereIn('status', ['PAGADA', 'POR_COMPROBAR', 'COMPROBACION_ACEPTADA', 'COMPROBACION_RECHAZADA'])
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $map = $rows->keyBy('d');

        $financeLine = [];
        $trend30     = [];
        for ($i = 0; $i < 30; $i++) {
            $day   = $start30->copy()->addDays($i)->toDateString();
            $monto = (float) ($map[$day]->monto ?? 0);
            $qty   = (int)   ($map[$day]->qty  ?? 0);

            // Línea financiera: monto total por día (pagado o pendiente)
            $financeLine[] = [
                'name'  => Carbon::parse($day, $tz)->format('d M'),
                'value' => $monto,
            ];

            // Tendencia: cantidad de requisiciones por día
            $trend30[] = [
                'name'  => Carbon::parse($day, $tz)->format('d M'),
                'value' => $qty,
                'value2'=> 0,
            ];
        }

        $quickLinks = [
            ['label' => 'Requisiciones', 'routeName' => 'requisiciones.index', 'description' => 'Prioriza pagos y cierre.'],
            ['label' => 'Ajustes',        'routeName' => 'ajustes.index',        'description' => 'Resuelve diferencias.'],
            ['label' => 'Folios',         'routeName' => 'folios.index',         'description' => 'Control de folios registrados.'],
        ];

        $kpis = [
            // Requisiciones pendientes de pago: CAPTURADA / PAGO_AUTORIZADO
            ['label' => 'Por pagar',      'value' => number_format($porPagar),      'hint' => 'CAPTURADA / PAGO AUTORIZADO'],
            // Requisiciones en espera de comprobantes
            ['label' => 'Por comprobar',  'value' => number_format($porComprobar),  'hint' => 'POR_COMPROBAR'],
            // Requisiciones cuya comprobación fue revisada en el mes
            ['label' => 'Comprobadas (mes)', 'value' => number_format($comprobadasMes), 'hint' => 'Comprobación revisada'],
            // Monto pagado en el mes (según fecha de pago)
            ['label' => 'Pagado (mes)',   'value' => '$ ' . number_format($montoPagadoMes, 2), 'hint' => 'Por fecha de pago'],
        ];

        return Inertia::render('Dashboard/ContadorDashboard', [
            'dashboard' => [
                'headline'    => 'Panel financiero',
                'subheadline' => 'Pagos, comprobación y control del gasto.',
                'userName'    => $user->name,
                'userRole'    => $user->rol,
                'kpis'        => $kpis,
                'trend30'     => $trend30,
                'financeLine' => $financeLine,
                'quickLinks'  => $quickLinks,
            ],
        ]);
    }

}
