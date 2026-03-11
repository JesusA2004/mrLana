<?php

namespace App\Http\Controllers\Exports;

use App\Exports\Requisiciones\RequisicionesExport;
use App\Models\Concepto;
use App\Models\Corporativo;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Sucursal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Maatwebsite\Excel\Facades\Excel;

class RequisicionExportController {

    public function excel(Request $request) {
        $rows = $this->buildRows($request);
        $filters = $this->presentFilters($request);
        return Excel::download(new RequisicionesExport($rows, $filters), 'requisiciones.xlsx');
    }

    public function pdf(Request $request) {
        $rows = $this->buildRows($request);
        $filters = $this->presentFilters($request);
        // Usa tu vista existente si ya la tienes; si no, crea una simple (tabla).
        $pdf = Pdf::loadView('exports.requisiciones.index', [
            'rows' => $rows,
            'filters' => $filters,
        ])->setPaper('letter', 'landscape');
        return $pdf->download('requisiciones.pdf');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildRows(Request $request): array {
        $q = trim((string)$request->query('q', ''));
        $tab = strtoupper((string)$request->query('tab', 'ACTIVAS'));
        $status = (string)$request->query('status', '');
        $corpId = $request->query('comprador_corp_id');
        $sucursalId = $request->query('sucursal_id');
        $solicitanteId = $request->query('solicitante_id');
        $conceptoId = $request->query('concepto_id');
        $proveedorId = $request->query('proveedor_id');
        $tipo = (string)$request->query('tipo', '');
        $from = $this->safeYmd($request->query('fecha_from'));
        $to   = $this->safeYmd($request->query('fecha_to'));
        $dir = strtolower((string)$request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $sortRaw = (string)$request->query('sort', 'created_at');
        $sort = $this->normalizeSort($sortRaw);
        $query = Requisicion::query()
            ->with([
                'sucursal:id,nombre,codigo,corporativo_id',
                'solicitante:id,nombre,apellido_paterno,apellido_materno',
                'proveedor:id,razon_social,rfc',
                'concepto:id,nombre',
                'comprador:id,nombre',
            ]);
        // Eliminación lógica: por default no exportes eliminadas, salvo que lo pidan
        if ($status === 'ELIMINADA' || $tab === 'ELIMINADAS') {
            $query->where('status', 'ELIMINADA');
        } else {
            $query->where('status', '!=', 'ELIMINADA');
        }
        if ($status === '') {
            switch ($tab) {
                case 'BORRADOR':
                    $query->where('status', 'BORRADOR');
                    break;
                case 'CAPTURADAS':
                    $query->whereNotIn('status', ['BORRADOR', 'ELIMINADA']);
                    break;
                case 'ELIMINADAS':
                    $query->where('status', 'ELIMINADA');
                    break;
                case 'ACTIVAS':
                default:
                    break;
            }
        } else {
            $query->where('status', $status);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('folio', 'like', "%{$q}%")
                    ->orWhere('observaciones', 'like', "%{$q}%")
                    ->orWhereHas('proveedor', fn($p) => $p->where('razon_social', 'like', "%{$q}%"))
                    ->orWhereHas('concepto', fn($c) => $c->where('nombre', 'like', "%{$q}%"))
                    ->orWhereHas('comprador', fn($c) => $c->where('nombre', 'like', "%{$q}%"))
                    ->orWhereHas('sucursal', fn($s) => $s->where('nombre', 'like', "%{$q}%"));
            });
        }
        if (!empty($corpId))       $query->where('comprador_corp_id', (int)$corpId);
        if (!empty($sucursalId))   $query->where('sucursal_id', (int)$sucursalId);
        if (!empty($solicitanteId)) $query->where('solicitante_id', (int)$solicitanteId);
        if (!empty($conceptoId))   $query->where('concepto_id', (int)$conceptoId);
        if (!empty($proveedorId))  $query->where('proveedor_id', (int)$proveedorId);
        if ($tipo !== '')          $query->where('tipo', $tipo);
        // Este era el crash: NO usamos dateRangeCaptura() inexistente.
        if ($from) $query->whereDate('created_at', '>=', $from);
        if ($to)   $query->whereDate('created_at', '<=', $to);

        $allowed = ['folio','created_at','monto_total','status','tipo','id'];
        if (!in_array($sort, $allowed, true)) $sort = 'created_at';

        $items = $query->orderBy($sort, $dir)->orderBy('id','desc')->get();
        $rows        = [];
        $lastFolio   = null;
        foreach ($items as $req) {
            $isFirstItem = true;
            foreach ($req->detalles as $detalle) {
                $rows[] = [
                    // Si es el primer detalle, se llenan los campos comunes; en los siguientes se dejan en blanco
                    'folio'         => $isFirstItem ? $req->folio : '',
                    'fecha_captura' => $isFirstItem ? optional($req->created_at)->format('Y-m-d H:i') : '',
                    'tipo'          => $isFirstItem ? $req->tipo : '',
                    'estatus'       => $isFirstItem ? $req->status : '',
                    'comprador'     => $isFirstItem ? $req->comprador?->nombre : '',
                    'sucursal'      => $isFirstItem ? $req->sucursal?->nombre : '',
                    'solicitante'   => $isFirstItem ? ($req->solicitante
                        ? trim($req->solicitante->nombre.' '.$req->solicitante->apellido_paterno.' '.($req->solicitante->apellido_materno ?? ''))
                        : '') : '',
                    'proveedor'     => $isFirstItem ? $req->proveedor?->razon_social : '',
                    'concepto'      => $isFirstItem ? $req->concepto?->nombre : '',

                    // Campos de detalle (siempre se muestran)
                    'cantidad'        => (float) $detalle->cantidad,
                    'descripcion_item'=> $detalle->descripcion,
                    'precio_unitario' => (float) $detalle->precio_unitario,
                    'genera_iva'      => $detalle->genera_iva ? 'Sí' : 'No',
                    'subtotal_item'   => (float) $detalle->subtotal,
                    'iva_item'        => (float) $detalle->iva,
                    'total_item'      => (float) $detalle->total,

                    // Totales de la requisición solo en la primera fila
                    'subtotal'        => $isFirstItem ? (float)($req->monto_subtotal ?? 0) : '',
                    'total'           => $isFirstItem ? (float)($req->monto_total    ?? 0) : '',
                ];
                $isFirstItem = false;
            }
        }

        return $rows;
    }

    /**
     * @return array<string, string>
     */
    private function presentFilters(Request $request): array {
        $sortRaw = (string)$request->query('sort', 'created_at');
        $sort = $this->normalizeSort($sortRaw);
        $sortLabel = match ($sort) {
            'created_at' => 'Fecha de captura',
            'folio' => 'Folio',
            'monto_total' => 'Total',
            'status' => 'Estatus',
            'tipo' => 'Tipo',
            default => 'Fecha de captura',
        };
        $dir = strtolower((string)$request->query('dir', 'desc')) === 'asc' ? 'Ascendente' : 'Descendente';
        $corpId = $request->query('comprador_corp_id');
        $sucursalId = $request->query('sucursal_id');
        $solicitanteId = $request->query('solicitante_id');
        $conceptoId = $request->query('concepto_id');
        $proveedorId = $request->query('proveedor_id');
        $corp = $corpId ? (Corporativo::select('id','nombre')->find((int)$corpId)?->nombre ?? "#{$corpId}") : '';
        $suc  = $sucursalId ? (Sucursal::select('id','nombre')->find((int)$sucursalId)?->nombre ?? "#{$sucursalId}") : '';
        $sol = $solicitanteId
        ? Empleado::select('id','nombre','apellido_paterno','apellido_materno')->find((int)$solicitanteId)
        : null;
        $solName = $sol ? trim($sol->nombre . ' ' . $sol->apellido_paterno . ' ' . ($sol->apellido_materno ?? '')) : '';
        $con  = $conceptoId ? (Concepto::select('id','nombre')->find((int)$conceptoId)?->nombre ?? "#{$conceptoId}") : '';
        $prov = $proveedorId ? (Proveedor::select('id','razon_social')->find((int)$proveedorId)?->razon_social ?? "#{$proveedorId}") : '';
        return array_filter([
            'Búsqueda' => trim((string)$request->query('q', '')),
            'Tab' => strtoupper((string)$request->query('tab', 'ACTIVAS')),
            'Estatus' => (string)$request->query('status', ''),
            'Corporativo' => $corp,
            'Sucursal' => $suc,
            'Solicitante' => $solName,
            'Concepto' => $con,
            'Proveedor' => $prov,
            'Tipo' => (string)$request->query('tipo', ''),
            'Captura desde' => (string)($request->query('fecha_from', '')),
            'Captura hasta' => (string)($request->query('fecha_to', '')),
            'Orden' => $sortLabel,
            'Dirección' => $dir,
        ], fn($v) => $v !== null && $v !== '');
    }

    private function safeYmd($v): ?string {
        if (!is_string($v) || $v === '') return null;
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    private function normalizeSort(string $sort): string {
        $map = [
            'fecha_captura' => 'created_at',
            'createdAt' => 'created_at',
            'created_at' => 'created_at',
            'folio' => 'folio',
            'monto_total' => 'monto_total',
            'status' => 'status',
            'tipo' => 'tipo',
            'id' => 'id',
        ];
        $sort = trim($sort);
        return $map[$sort] ?? 'created_at';
    }

}
