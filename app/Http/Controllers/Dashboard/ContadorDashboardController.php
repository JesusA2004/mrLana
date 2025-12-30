<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContadorDashboardController extends Controller {

    public function index(): Response {
        $user = auth()->user();

        $tz = config('app.timezone', 'America/Mexico_City');
        $today = Carbon::now($tz);
        $start30 = $today->copy()->subDays(29)->startOfDay();
        $startMonth = $today->copy()->startOfMonth();
        $endMonth = $today->copy()->endOfMonth();

        $porPagar = DB::table('requisicions')->where('status', 'CAPTURADA')->count();
        $porComprobar = DB::table('requisicions')->where('status', 'POR_COMPROBAR')->count();
        $comprobadasMes = DB::table('requisicions')
            ->where('status', 'COMPROBADA')
            ->whereBetween('fecha_captura', [$startMonth, $endMonth])
            ->count();

        $montoPagadoMes = (float) DB::table('requisicions')
            ->where('status', 'PAGADA')
            ->whereBetween('fecha_pago', [$startMonth->toDateString(), $endMonth->toDateString()])
            ->sum('monto_total');

        // Finance line (30 días): monto por fecha_pago si existe, si no usa fecha_captura
        $rows = DB::table('requisicions')
            ->selectRaw("DATE(COALESCE(fecha_pago, fecha_captura)) as d, SUM(monto_total) as monto, COUNT(*) as qty")
            ->whereRaw("COALESCE(fecha_pago, fecha_captura) >= ?", [$start30])
            ->whereIn('status', ['PAGADA', 'POR_COMPROBAR', 'COMPROBADA'])
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $map = $rows->keyBy('d');

        $financeLine = [];
        $trend30 = [];
        for ($i = 0; $i < 30; $i++) {
            $day = $start30->copy()->addDays($i)->toDateString();
            $monto = (float) (($map[$day]->monto ?? 0));
            $qty = (int) (($map[$day]->qty ?? 0));

            $financeLine[] = [
                'name' => Carbon::parse($day, $tz)->format('d M'),
                'value' => $monto,
            ];

            $trend30[] = [
                'name' => Carbon::parse($day, $tz)->format('d M'),
                'value' => $qty,
                'value2' => 0,
            ];
        }

        $quickLinks = [
            ['label' => 'Requisiciones', 'routeName' => 'requisiciones.index', 'description' => 'Prioriza pagos y cierre.'],
            ['label' => 'Ajustes', 'routeName' => 'ajustes.index', 'description' => 'Resuelve diferencias.'],
            ['label' => 'Folios', 'routeName' => 'folios.index', 'description' => 'Control de folios registrados.'],
        ];

        $kpis = [
            ['label' => 'Por pagar', 'value' => number_format($porPagar), 'hint' => 'Estatus CAPTURADA.'],
            ['label' => 'Por comprobar', 'value' => number_format($porComprobar), 'hint' => 'Estatus POR_COMPROBAR.'],
            ['label' => 'Comprobadas (mes)', 'value' => number_format($comprobadasMes), 'hint' => 'Cierre del periodo.'],
            ['label' => 'Pagado (mes)', 'value' => '$ ' . number_format($montoPagadoMes, 2), 'hint' => 'Por fecha_pago.'],
        ];

        return Inertia::render('Dashboard/ContadorDashboard', [
            'dashboard' => [
                'headline' => 'Panel financiero',
                'subheadline' => 'Pagos, comprobación y control del gasto.',
                'userName' => $user->name,
                'userRole' => $user->rol,
                'kpis' => $kpis,
                'trend30' => $trend30,
                'financeLine' => $financeLine,
                'quickLinks' => $quickLinks,
            ],
        ]);
    }

}
