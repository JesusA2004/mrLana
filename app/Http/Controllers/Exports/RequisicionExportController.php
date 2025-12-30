<?php

namespace App\Http\Controllers\Exports;

use App\Http\Controllers\Controller;
use App\Exports\Requisiciones\RequisicionesExport;
use App\Models\Requisicion;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class RequisicionExportController extends Controller
{
    public function pdf(Request $request)
    {
        $rows = $this->buildRows($request);

        $meta = [
            'title' => 'Reporte de Requisiciones',
            'subtitle' => 'Exportación con filtros actuales',
            'generated_at' => now()->format('Y-m-d H:i'),
            'generated_by' => optional($request->user())->name,
            'footer_left' => 'ERP MR-Lana',
        ];

        $filters = $this->filtersLabel($request);

        $pdf = Pdf::loadView('exports.requisiciones.index', [
            'rows' => $rows,
            'filters' => $filters,
            'meta' => $meta,
            'totals' => ['total' => count($rows)],
        ])->setPaper('a4', 'landscape');

        return $pdf->download('requisiciones.pdf');
    }

    public function excel(Request $request)
    {
        $rows = $this->buildRows($request);

        $meta = [
            'title' => 'Reporte de Requisiciones',
            'subtitle' => 'Exportación con filtros actuales',
            'generated_at' => now()->format('Y-m-d H:i'),
            'generated_by' => optional($request->user())->name,
        ];

        $filters = $this->filtersLabel($request);

        return Excel::download(new RequisicionesExport($rows, $filters, $meta), 'requisiciones.xlsx');
    }

    private function buildRows(Request $request): array
    {
        /**
         * Parámetros esperados desde Vue (alineado a UI típica):
         * - corporativo_id (beneficiario/dueño)   [opcional]
         * - comprador_corp_id                    [opcional]
         * - sucursal_id                          [opcional]
         * - solicitante_id                       [opcional]
         * - proveedor_id                         [opcional]
         * - concepto_id                          [opcional]
         * - tipo                                 [opcional]
         * - tab/statusTab                         [TODAS|PENDIENTES|APROBADAS|RECHAZADAS|STATUS_EXACTO]
         * - q                                    [folio/observaciones]
         * - from/to                              [rango fecha_captura]
         * - sort                                 [folio|fecha_captura|monto_total|status]
         * - dir                                  [asc|desc]
         */
        $corporativoId  = $request->integer('corporativo_id') ?: null;
        $compradorId    = $request->integer('comprador_corp_id') ?: null;
        $sucursalId     = $request->integer('sucursal_id') ?: null;
        $solicitanteId  = $request->integer('solicitante_id') ?: null;
        $proveedorId    = $request->integer('proveedor_id') ?: null;
        $conceptoId     = $request->integer('concepto_id') ?: null;

        $tipo   = trim((string) $request->get('tipo', ''));
        $tab    = (string) $request->get('tab', $request->get('statusTab', 'TODAS'));
        $q      = trim((string) $request->get('q', ''));
        $from   = trim((string) $request->get('from', $request->get('fecha_from', '')));
        $to     = trim((string) $request->get('to', $request->get('fecha_to', '')));

        $sort = (string) $request->get('sort', 'fecha_captura');
        $dir  = strtolower((string) $request->get('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Requisicion::query()
            ->with([
                'comprador:id,nombre',
                'corporativo:id,nombre',
                'sucursal:id,corporativo_id,nombre',
                'solicitante:id,nombre,apellido_paterno,apellido_materno',
                'proveedor:id,nombre',
                'concepto:id,nombre',
                'creadaPor:id,name,email',
            ]);

        // Filtros directos
        if ($corporativoId) $query->where('corporativo_id', $corporativoId);
        if ($compradorId)   $query->where('comprador_corp_id', $compradorId);
        if ($sucursalId)    $query->where('sucursal_id', $sucursalId);
        if ($solicitanteId) $query->where('solicitante_id', $solicitanteId);
        if ($proveedorId)   $query->where('proveedor_id', $proveedorId);
        if ($conceptoId)    $query->where('concepto_id', $conceptoId);

        if ($tipo !== '') $query->where('tipo', $tipo);

        // Scopes existentes
        $query->search($q)->statusTab($tab)->dateRangeCaptura($from, $to);

        // Orden permitido
        $allowedSort = ['folio', 'fecha_captura', 'monto_total', 'status', 'tipo', 'id'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'fecha_captura';

        $query->orderBy($sort, $dir)->orderBy('id', 'desc');

        $items = $query->get();

        // Flatten a array simple para PDF/Excel
        return $items->map(function (Requisicion $r) {
            $sol = trim(
                (string) ($r->solicitante?->nombre ?? '') . ' ' .
                (string) ($r->solicitante?->apellido_paterno ?? '') . ' ' .
                (string) ($r->solicitante?->apellido_materno ?? '')
            );

            return [
                'id' => $r->id,
                'folio' => (string) ($r->folio ?? ''),
                'tipo' => (string) ($r->tipo ?? ''),
                'status' => (string) ($r->status ?? ''),
                'comprador' => (string) ($r->comprador?->nombre ?? '—'),
                'corporativo' => (string) ($r->corporativo?->nombre ?? '—'),
                'sucursal' => (string) ($r->sucursal?->nombre ?? '—'),
                'solicitante' => ($sol !== '' ? $sol : '—'),
                'proveedor' => (string) ($r->proveedor?->nombre ?? '—'),
                'concepto' => (string) ($r->concepto?->nombre ?? '—'),
                'subtotal' => (float) ($r->monto_subtotal ?? 0),
                'iva' => (float) ($r->monto_iva ?? 0),
                'total' => (float) ($r->monto_total ?? 0),
                'fecha_captura' => optional($r->fecha_captura)?->format('Y-m-d H:i'),
                'fecha_pago' => optional($r->fecha_pago)?->format('Y-m-d'),
                'created_by' => (string) ($r->creadaPor?->name ?? '—'),
                'created_at' => optional($r->created_at)?->format('Y-m-d H:i'),
            ];
        })->values()->all();
    }

    private function filtersLabel(Request $request): array
    {
        return [
            'Corporativo'  => $request->get('corporativo_id'),
            'Comprador'    => $request->get('comprador_corp_id'),
            'Sucursal'     => $request->get('sucursal_id'),
            'Solicitante'  => $request->get('solicitante_id'),
            'Proveedor'    => $request->get('proveedor_id'),
            'Concepto'     => $request->get('concepto_id'),
            'Tipo'         => $request->get('tipo'),
            'Tab/Estatus'  => $request->get('tab', $request->get('statusTab')),
            'Búsqueda'     => $request->get('q'),
            'Desde'        => $request->get('from', $request->get('fecha_from')),
            'Hasta'        => $request->get('to', $request->get('fecha_to')),
            'Orden'        => $request->get('sort'),
            'Dirección'    => $request->get('dir'),
        ];
    }

}
