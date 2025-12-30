<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ColaboradorDashboardController extends Controller {

    public function index(): Response {
        $user = auth()->user();

        $tz = config('app.timezone', 'America/Mexico_City');
        $today = Carbon::now($tz);
        $start30 = $today->copy()->subDays(29)->startOfDay();
        $startMonth = $today->copy()->startOfMonth();
        $endMonth = $today->copy()->endOfMonth();

        $userId = (int) $user->id;
        $empleadoId = $user->empleado_id ? (int) $user->empleado_id : null;

        // Scope base (mis requisiciones)
        $base = DB::table('requisicions')
            ->where(function ($q) use ($userId, $empleadoId) {
                $q->where('creada_por_user_id', $userId);
                if ($empleadoId) {
                    $q->orWhere('solicitante_id', $empleadoId);
                }
            });

        $misMes = (clone $base)
            ->whereBetween('fecha_captura', [$startMonth, $endMonth])
            ->count();

        $misPendientes = (clone $base)
            ->whereIn('status', ['BORRADOR', 'CAPTURADA'])
            ->count();

        $misPorComprobar = (clone $base)
            ->where('status', 'POR_COMPROBAR')
            ->count();

        $montoMes = (float) (clone $base)
            ->whereBetween('fecha_captura', [$startMonth, $endMonth])
            ->sum('monto_total');

        // Trend 30 días (mis requisiciones)
        $rows = (clone $base)
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
                'value' => (int) (($map[$day]->qty ?? 0)),
                'value2' => (float) (($map[$day]->monto ?? 0)),
            ];
        }

        $quickLinks = [
            ['label' => 'Registrar requisición', 'routeName' => 'requisiciones.registrar', 'description' => 'Captura rápida y ordenada.'],
            ['label' => 'Mis requisiciones', 'routeName' => 'requisiciones.index', 'description' => 'Seguimiento y estatus.'],
        ];

        $kpis = [
            ['label' => 'Mis requisiciones (mes)', 'value' => number_format($misMes), 'hint' => 'Volumen del periodo.'],
            ['label' => 'Pendientes', 'value' => number_format($misPendientes), 'hint' => 'BORRADOR / CAPTURADA.'],
            ['label' => 'Por comprobar', 'value' => number_format($misPorComprobar), 'hint' => 'Pendiente de evidencia.'],
            ['label' => 'Monto del mes', 'value' => '$ ' . number_format($montoMes, 2), 'hint' => 'Total capturado por ti.'],
        ];

        return Inertia::render('Dashboard/ColaboradorDashboard', [
            'dashboard' => [
                'headline' => 'Tu panel',
                'subheadline' => 'Tus requisiciones y prioridades del día.',
                'userName' => $user->name,
                'userRole' => $user->rol,
                'kpis' => $kpis,
                'trend30' => $trend30,
                'financeLine' => [],
                'quickLinks' => $quickLinks,
            ],
        ]);
    }

}
