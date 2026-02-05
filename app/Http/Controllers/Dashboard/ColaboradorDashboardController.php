<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ColaboradorDashboardController extends Controller {

    /**
     * Muestra el panel personalizado para el colaborador.
     * Filtra las requisiciones creadas por el usuario o donde él es solicitante.
     */
    public function index(): Response {
        $user = auth()->user();

        $tz = config('app.timezone', 'America/Mexico_City');
        $today     = Carbon::now($tz);
        $start30   = $today->copy()->subDays(29)->startOfDay();
        $startMonth= $today->copy()->startOfMonth();
        $endMonth  = $today->copy()->endOfMonth();

        $userId     = (int) $user->id;
        $empleadoId = $user->empleado_id ? (int) $user->empleado_id : null;

        // Base de requisiciones del colaborador (creadas por él o solicitadas por él)
        $base = DB::table('requisicions')
            ->where(function ($q) use ($userId, $empleadoId) {
                $q->where('creada_por_user_id', $userId);
                if ($empleadoId) {
                    $q->orWhere('solicitante_id', $empleadoId);
                }
            });

        // Número de requisiciones del mes actual (por fecha de solicitud)
        $misMes = (clone $base)
            ->whereBetween('fecha_solicitud', [$startMonth, $endMonth])
            ->count();

        // Requisiciones pendientes (incluye BORRADOR, CAPTURADA, PAGO_AUTORIZADO y POR_COMPROBAR)
        $misPendientes = (clone $base)
            ->whereIn('status', ['BORRADOR', 'CAPTURADA', 'PAGO_AUTORIZADO', 'POR_COMPROBAR'])
            ->count();

        // Requisiciones en espera de comprobación
        $misPorComprobar = (clone $base)
            ->where('status', 'POR_COMPROBAR')
            ->count();

        // Monto total de requisiciones del colaborador en el mes actual
        $montoMes = (float) (clone $base)
            ->whereBetween('fecha_solicitud', [$startMonth, $endMonth])
            ->sum('monto_total');

        // Serie de tendencia de los últimos 30 días (cantidad y monto por día)
        $rows = (clone $base)
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
                'value' => (int)   ($map[$day]->qty  ?? 0), // número de requisiciones
                'value2'=> (float) ($map[$day]->monto ?? 0), // monto total
            ];
        }

        $quickLinks = [
            ['label' => 'Registrar requisición', 'routeName' => 'requisiciones.registrar', 'description' => 'Captura rápida y ordenada.'],
            ['label' => 'Mis requisiciones',     'routeName' => 'requisiciones.index',     'description' => 'Seguimiento y estatus.'],
        ];

        $kpis = [
            // Total de requisiciones creadas por el colaborador en el mes actual
            ['label' => 'Mis requisiciones (mes)', 'value' => number_format($misMes),      'hint' => 'Volumen del periodo.'],
            // Pendientes (incluye BORRADOR, CAPTURADA, POR COMPROBAR y PAGO AUTORIZADO)
            ['label' => 'Pendientes',              'value' => number_format($misPendientes),'hint' => 'BORRADOR / CAPTURADA / POR COMPROBAR / PAGO AUTORIZADO'],
            // Requisiciones en estado POR_COMPROBAR
            ['label' => 'Por comprobar',            'value' => number_format($misPorComprobar), 'hint' => 'Evidencia pendiente.'],
            // Suma total de las requisiciones del colaborador en el mes
            ['label' => 'Monto del mes',            'value' => '$ ' . number_format($montoMes, 2), 'hint' => 'Total capturado por ti.'],
        ];

        return Inertia::render('Dashboard/ColaboradorDashboard', [
            'dashboard' => [
                'headline'    => 'Tu panel',
                'subheadline' => 'Tus requisiciones y prioridades del día.',
                'userName'    => $user->name,
                'userRole'    => $user->rol,
                'kpis'        => $kpis,
                'trend30'     => $trend30,
                'financeLine' => [], // El colaborador no necesita serie financiera
                'quickLinks'  => $quickLinks,
            ],
        ]);
    }

}
