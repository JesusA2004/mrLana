<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardDataService
{
    /**
     * Genera el payload del dashboard para Admin/Contador/Colaborador.
     * IMPORTANTE: Tu BD real usa requisicions.fecha_solicitud (NO fecha_captura)
     */
    public function build(string $role): array
    {
        $role = strtoupper(trim($role));

        $now = Carbon::now();
        $startMonth = $now->copy()->startOfMonth()->startOfDay();
        $endMonth   = $now->copy()->endOfMonth()->endOfDay();

        $start14 = $now->copy()->subDays(13)->startOfDay(); // 14 días contando hoy
        $start30 = $now->copy()->subDays(29)->startOfDay();

        // =========
        // KPIs (arriba)
        // =========
        $corporativosActivos = DB::table('corporativos')->where('activo', 1)->count();
        $sucursalesActivas   = DB::table('sucursals')->where('activo', 1)->count();
        $empleadosActivos    = DB::table('empleados')->where('activo', 1)->count();

        // Monto del mes (por fecha_solicitud)
        $montoMes = (float) DB::table('requisicions')
            ->whereBetween('fecha_solicitud', [$startMonth, $endMonth])
            ->sum('monto_total');

        $kpis = [
            ['label' => 'Corporativos activos', 'value' => (string) $corporativosActivos, 'hint' => 'Operación base'],
            ['label' => 'Sucursales activas',   'value' => (string) $sucursalesActivas,   'hint' => 'Cobertura'],
            ['label' => 'Empleados activos',    'value' => (string) $empleadosActivos,    'hint' => 'Capacidad'],
            ['label' => 'Monto del mes',        'value' => '$' . number_format($montoMes, 2), 'hint' => 'Mes en curso'],
        ];

        // =========
        // ActivityDaily (14 días) -> conteo requisiciones por fecha_solicitud
        // =========
        $activityRows = DB::table('requisicions')
            ->selectRaw("DATE(fecha_solicitud) as d, COUNT(*) as qty")
            ->where('fecha_solicitud', '>=', $start14)
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        // AmountsDaily (14 días) -> sum monto_total por fecha_solicitud
        $amountRows = DB::table('requisicions')
            ->selectRaw("DATE(fecha_solicitud) as d, SUM(monto_total) as monto")
            ->where('fecha_solicitud', '>=', $start14)
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $activityDaily = [];
        $amountsDaily  = [];
        for ($i = 0; $i < 14; $i++) {
            $day = $start14->copy()->addDays($i);
            $key = $day->toDateString(); // Y-m-d

            $qty   = isset($activityRows[$key]) ? (int) $activityRows[$key]->qty : 0;
            $monto = isset($amountRows[$key])   ? (float) $amountRows[$key]->monto : 0.0;

            // name = etiqueta (la usas en Vue). Manténla simple.
            $label = $day->format('d M');

            $activityDaily[] = ['name' => $label, 'value' => $qty];
            $amountsDaily[]  = ['name' => $label, 'value' => round($monto, 2)];
        }

        // =========
        // StatusMix (últimos 30 días) por fecha_solicitud
        // =========
        $statusBase = [
            'BORRADOR' => 0,
            'ELIMINADA' => 0,
            'CAPTURADA' => 0,
            'PAGO_AUTORIZADO' => 0,
            'PAGO_RECHAZADO' => 0,
            'PAGADA' => 0,
            'POR_COMPROBAR' => 0,
            'COMPROBACION_ACEPTADA' => 0,
            'COMPROBACION_RECHAZADA' => 0,
        ];

        $statusRows = DB::table('requisicions')
            ->selectRaw("status as s, COUNT(*) as c")
            ->where('fecha_solicitud', '>=', $start30)
            ->groupBy('s')
            ->get();

        foreach ($statusRows as $r) {
            $k = (string) $r->s;
            if (array_key_exists($k, $statusBase)) {
                $statusBase[$k] = (int) $r->c;
            }
        }

        $statusMix = [];
        foreach ($statusBase as $name => $count) {
            // puedes filtrar ceros si quieres, pero para charts es ok
            $statusMix[] = ['name' => $name, 'value' => $count];
        }

        // =========
        // ComprobantesMix (mes) -> tu BD real: comprobantes.fecha_emision (fallback created_at)
        // =========
        $compBase = ['FACTURA' => 0, 'TICKET' => 0, 'NOTA' => 0, 'OTRO' => 0];

        $compRows = DB::table('comprobantes')
            ->selectRaw("tipo_doc as t, COUNT(*) as c")
            ->where(function ($q) use ($startMonth, $endMonth) {
                $q->whereBetween('fecha_emision', [$startMonth->toDateString(), $endMonth->toDateString()])
                  ->orWhere(function ($q2) use ($startMonth, $endMonth) {
                      $q2->whereNull('fecha_emision')
                         ->whereBetween('created_at', [$startMonth, $endMonth]);
                  });
            })
            ->groupBy('t')
            ->get();

        foreach ($compRows as $r) {
            $k = (string) $r->t;
            if (array_key_exists($k, $compBase)) {
                $compBase[$k] = (int) $r->c;
            }
        }

        $comprobantesMix = [];
        foreach ($compBase as $name => $count) {
            $comprobantesMix[] = ['name' => $name, 'value' => $count];
        }

        // =========
        // Headline/Subheadline por rol
        // =========
        $headline = match ($role) {
            'ADMIN' => 'Panel ejecutivo',
            'CONTADOR' => 'Panel financiero',
            default => 'Mi operación',
        };

        $subheadline = match ($role) {
            'ADMIN' => 'KPIs globales + pulso de operación (14 días).',
            'CONTADOR' => 'Control de flujo, montos y comprobación.',
            default => 'Visibilidad de tu actividad y pendientes.',
        };

        return [
            'headline' => $headline,
            'subheadline' => $subheadline,
            'userRole' => $role,
            // userName lo puede meter controller desde auth; si no, lo dejamos vacío
            'kpis' => $kpis,

            // NUEVO payload (el que ya consumes)
            'activityDaily' => $activityDaily,
            'amountsDaily' => $amountsDaily,
            'statusMix' => $statusMix,
            'comprobantesMix' => $comprobantesMix,
        ];
    }
}
