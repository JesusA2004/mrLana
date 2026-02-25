<?php

namespace App\Http\Controllers\Exports;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardDataService;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Dashboard\DashboardExcelExport;

class DashboardExportController extends Controller
{
    public function __construct(private DashboardDataService $service) {}

    /**
     * Convierte lista/assoc a mapa asociativo string=>number
     * Soporta:
     *  - assoc: ['APROBADO' => 10]
     *  - lista: [['name'=>'APROBADO','value'=>10], ...]
     */
    private function listToMap($value): array
    {
        if (!is_array($value)) return [];

        $isList = array_keys($value) === range(0, count($value) - 1);

        // Si ya es assoc k=>v
        if (!$isList) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[(string)$k] = is_numeric($v) ? (float)$v : (float)($v ?? 0);
            }
            return $out;
        }

        // Si es lista [{name,value}]
        $out = [];
        foreach ($value as $row) {
            $name = is_array($row) ? ($row['name'] ?? null) : (is_object($row) ? ($row->name ?? null) : null);
            $val  = is_array($row) ? ($row['value'] ?? 0)   : (is_object($row) ? ($row->value ?? 0) : 0);

            if ($name === null) continue;
            $out[(string)$name] = is_numeric($val) ? (float)$val : (float)($val ?? 0);
        }
        return $out;
    }

    /**
     * Normaliza puntos a lo que tu Blade espera:
     * [{date, value}]
     * Soporta input con name/date + value
     */
    private function pointsToBlade(array $points): array
    {
        $out = [];
        foreach ($points as $p) {
            $name = is_array($p)
                ? ($p['name'] ?? ($p['date'] ?? ''))
                : (is_object($p) ? ($p->name ?? ($p->date ?? '')) : '');

            $val = is_array($p)
                ? ($p['value'] ?? 0)
                : (is_object($p) ? ($p->value ?? 0) : 0);

            $out[] = [
                'date'  => (string)$name,
                'value' => is_numeric($val) ? (float)$val : 0.0,
            ];
        }
        return $out;
    }

    /**
     * =========================
     * PDF
     * =========================
     * GET /exports/dashboard/{role}/pdf
     * name: dashboard.export.pdf
     * Descarga directa (NO stream) para que no te abra pestaña ni te "saque".
     */
    public function pdf(Request $request, string $role)
    {
        $role = strtoupper(trim($role));
        if (!in_array($role, ['ADMIN', 'CONTADOR', 'COLABORADOR'], true)) {
            abort(404);
        }

        // Fuente de verdad
        $data = $this->service->build($role);

        $generatedAt = now()->format('Y-m-d H:i');

        // Normaliza series EXACTO igual que Excel
        $activity = $data['activityDaily'] ?? $data['trend30'] ?? [];
        $amounts  = $data['amountsDaily']  ?? $data['financeLine'] ?? [];

        $data['activityDaily'] = $this->pointsToBlade(is_array($activity) ? $activity : []);
        $data['amountsDaily']  = $this->pointsToBlade(is_array($amounts)  ? $amounts  : []);

        // Blade ADMIN usa @foreach(($data['statusMix'] ?? []) as $k => $v)
        // => debe ser MAPA, no lista
        $data['statusMix']       = $this->listToMap($data['statusMix'] ?? $data['byStatus'] ?? []);
        $data['comprobantesMix'] = $this->listToMap($data['comprobantesMix'] ?? $data['typesPie'] ?? []);

        $data['kpis'] = is_array($data['kpis'] ?? null) ? $data['kpis'] : [];

        // Render
        $fileName = 'dashboard_' . strtolower($role) . '_' . now()->format('Ymd_His') . '.pdf';

        $pdf = Pdf::loadView('exports.dashboard.report', [
            'role'        => $role,
            'generatedAt' => $generatedAt,
            'data'        => $data,
        ])->setPaper('a4', 'portrait');

        // ✅ descarga directa
        return $pdf->download($fileName);
    }

    /**
     * =========================
     * EXCEL
     * =========================
     * GET /exports/dashboard/{role}/excel
     * name: dashboard.export.excel
     * (Este es tu Excel que ya sirve)
     */
    public function excel(Request $request, string $role)
    {
        $role = strtoupper(trim($role));
        if (!in_array($role, ['ADMIN', 'CONTADOR', 'COLABORADOR'], true)) {
            abort(404);
        }

        $data = $this->service->build($role);

        $generatedAt = now()->format('Y-m-d H:i');

        // Normaliza series para Excel igual que PDF/Blade
        $activity = $data['activityDaily'] ?? $data['trend30'] ?? [];
        $amounts  = $data['amountsDaily']  ?? $data['financeLine'] ?? [];

        $data['activityDaily'] = $this->pointsToBlade(is_array($activity) ? $activity : []);
        $data['amountsDaily']  = $this->pointsToBlade(is_array($amounts)  ? $amounts  : []);

        $data['statusMix']       = $this->listToMap($data['statusMix'] ?? $data['byStatus'] ?? []);
        $data['comprobantesMix'] = $this->listToMap($data['comprobantesMix'] ?? $data['typesPie'] ?? []);

        $data['kpis'] = is_array($data['kpis'] ?? null) ? $data['kpis'] : [];

        $filename = 'dashboard_' . strtolower($role) . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new DashboardExcelExport($role, $generatedAt, $data),
            $filename
        );
    }
}
