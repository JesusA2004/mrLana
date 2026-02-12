<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewComprobanteRequest;
use App\Http\Requests\StoreComprobanteRequest;
use App\Http\Resources\ComprobanteResource;
use App\Models\Comprobante;
use App\Models\Requisicion;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RequisicionComprobanteController extends Controller {

    public function create(Requisicion $requisicion) {
        // Ajusta relaciones según tu Requisicion model
        $requisicion->load([
            'solicitante',
            'comprador',
            'sucursal',
            'proveedor',
            'concepto',
        ]);
        $comprobantes = $requisicion->comprobantes()->latest('id')->get();
        $sumAprobados = (float) $comprobantes
            ->where('estatus', 'APROBADO')
            ->sum(fn ($c) => (float) $c->monto);
        $sumCargados = (float) $comprobantes
            ->sum(fn ($c) => (float) $c->monto);
        $totalReq = (float) $requisicion->monto_total;
        return Inertia::render('Requisiciones/Comprobar', [
            'requisicion' => [
                'data' => [
                    'id' => $requisicion->id,
                    'folio' => $requisicion->folio,
                    'concepto' => $requisicion->concepto ?? ($requisicion->concepto?->nombre ?? null),
                    'monto_total' => (float) $requisicion->monto_total,
                    'solicitante_nombre' => $this->safeNombre($requisicion->solicitante),
                    // Facturación: ajusta a tu esquema real de corporativo
                    'razon_social' => $requisicion->comprador_corp?->nombre ?? null,
                    'rfc' => $requisicion->comprador_corp?->rfc ?? null,
                    'direccion' => $requisicion->comprador_corp?->direccion ?? null,
                    'correo' => $requisicion->comprador_corp?->correo ?? null,
                ],
            ],
            'comprobantes' => ComprobanteResource::collection($comprobantes),
            'totales' => [
                'cargado' => $sumCargados,
                'aprobado' => $sumAprobados,
                'pendiente_por_comprobar' => max(0, $totalReq - $sumCargados),
                'pendiente_por_aprobar' => max(0, $totalReq - $sumAprobados),
            ],
            'tipoDocOptions' => [
                ['id' => 'FACTURA', 'nombre' => 'Factura'],
                ['id' => 'TICKET', 'nombre' => 'Ticket'],
                ['id' => 'NOTA', 'nombre' => 'Nota'],
                ['id' => 'OTRO', 'nombre' => 'Otro'],
            ],
            // Ajusta a tus roles/permisos
            'canReview' => in_array((string) (auth()->user()?->role ?? ''), ['ADMIN', 'FINANZAS'], true),
        ]);
    }

    public function store(StoreComprobanteRequest $request, Requisicion $requisicion) {
        return DB::transaction(function () use ($request, $requisicion) {
            $file = $request->file('archivo');
            $folder = "comp_gasto/{$requisicion->id}";
            $stored = $file->storePublicly($folder, 'public');
            Comprobante::create([
                'requisicion_id' => $requisicion->id,
                'tipo_doc' => $request->input('tipo_doc'),
                'fecha_emision' => $request->input('fecha_emision'),
                'monto' => (float) $request->input('monto'),
                'archivo_path' => $stored,
                'archivo_original' => $file->getClientOriginalName(),
                'estatus' => 'PENDIENTE',
                'comentario_revision' => null,
                'user_revision_id' => null,
                'revisado_at' => null,
                'user_carga_id' => (int) auth()->id(),
            ]);
            // Recomendación: mantener requisición "POR_COMPROBAR" mientras no esté cubierta por aprobados
            // (esto evita castigar toda la requisición por 1 comprobante rechazado)
            if (property_exists($requisicion, 'status') || isset($requisicion->status)) {
                $requisicion->update(['status' => 'POR_COMPROBAR']);
            }
            return back()->with('success', 'Comprobante cargado.');
        });
    }

    public function review(ReviewComprobanteRequest $request, Comprobante $comprobante) {
        $estatus = $request->input('estatus');
        $coment = $request->input('comentario_revision');
        return DB::transaction(function () use ($comprobante, $estatus, $coment) {
            $comprobante->update([
                'estatus' => $estatus,
                'comentario_revision' => $coment,
                'user_revision_id' => (int) auth()->id(),
                'revisado_at' => now(),
            ]);
            $requisicion = $comprobante->requisicion()->first();
            if ($requisicion) {
                $sumAprobados = (float) $requisicion->comprobantes()
                    ->where('estatus', 'APROBADO')
                    ->sum('monto');
                // Regla global: aceptada si aprobados cubren el total
                if ((float) $requisicion->monto_total <= $sumAprobados) {
                    if (property_exists($requisicion, 'status') || isset($requisicion->status)) {
                        $requisicion->update(['status' => 'COMPROBACION_ACEPTADA']);
                    }
                } else {
                    if (property_exists($requisicion, 'status') || isset($requisicion->status)) {
                        $requisicion->update(['status' => 'POR_COMPROBAR']);
                    }
                }
            }
            return back()->with('success', 'Revisión aplicada.');
        });
    }

    private function safeNombre($model): string {
        if (!$model) return '—';
        foreach (['nombre_completo', 'nombreCompleto', 'name', 'nombre'] as $k) {
            if (!empty($model->{$k})) return (string) $model->{$k};
        }
        $parts = [];
        foreach (['nombre', 'nombres', 'apellido_paterno', 'apellido_materno', 'apellidoPaterno', 'apellidoMaterno'] as $k) {
            if (!empty($model->{$k})) $parts[] = $model->{$k};
        }
        $txt = trim(implode(' ', $parts));
        return $txt !== '' ? $txt : '—';
    }

}
