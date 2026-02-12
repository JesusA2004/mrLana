<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pagos\StorePagoRequest;
use App\Http\Resources\PagoResource;
use App\Models\Pago;
use App\Models\Requisicion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RequisicionPagoController extends Controller {

    public function create(Requisicion $requisicion) {
        $requisicion->load(['proveedor', 'concepto', 'solicitante']);
        $pagos = $requisicion->pagos()->latest('id')->get();
        $pagado = (float) $pagos->sum('monto');
        $total = (float) $requisicion->monto_total;
        $pendiente = max(0, $total - $pagado);
        $benef = $this->buildBeneficiario($requisicion);

        return Inertia::render('Requisiciones/Pagar', [
            'requisicion' => [
                'data' => [
                    'id' => $requisicion->id,
                    'folio' => $requisicion->folio,
                    'concepto' => $requisicion->concepto?->nombre ?? null,
                    'monto_total' => (float) $requisicion->monto_total,
                    'solicitante_nombre' => $this->safeNombre($requisicion->solicitante),
                    'beneficiario' => $benef,
                    'status' => $requisicion->status,
                ],
            ],
            'pagos' => PagoResource::collection($pagos),
            'totales' => [
                'pagado' => $pagado,
                'pendiente' => $pendiente,
            ],
            'tipoPagoOptions' => [
                ['id' => 'TRANSFERENCIA', 'nombre' => 'Transferencia'],
                ['id' => 'EFECTIVO', 'nombre' => 'Efectivo'],
                ['id' => 'TARJETA', 'nombre' => 'Tarjeta'],
                ['id' => 'CHEQUE', 'nombre' => 'Cheque'],
                ['id' => 'OTRO', 'nombre' => 'Otro'],
            ],
        ]);
    }

    public function store(StorePagoRequest $request, Requisicion $requisicion) {
        $requisicion->load(['proveedor', 'solicitante']);

        return DB::transaction(function () use ($request, $requisicion) {
            $pagado = (float) $requisicion->pagos()->sum('monto');
            $pendiente = max(0, (float)$requisicion->monto_total - $pagado);

            $monto = (float) $request->input('monto');
            if ($monto > $pendiente + 0.00001) {
                return back()->withErrors([
                    'monto' => "El monto excede lo pendiente por pagar ($pendiente).",
                ]);
            }

            $file = $request->file('archivo');
            $folder = "comprobantes/{$requisicion->id}/pagos";
            $stored = $file->storePublicly($folder, 'public');

            $benef = $this->buildBeneficiario($requisicion);

            Pago::create([
                'requisicion_id' => $requisicion->id,
                'beneficiario_nombre' => $benef['nombre'] ?? '—',
                'cuenta' => $benef['cuenta'] ?? null,
                'clabe' => $benef['clabe'] ?? null,
                'banco' => $benef['banco'] ?? null,
                'tipo_pago' => $request->input('tipo_pago'),
                'monto' => $monto,
                'fecha_pago' => $request->input('fecha_pago'),
                'archivo_path' => $stored,
                'archivo_original' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'referencia' => $request->input('referencia'),
                'user_carga_id' => (int) auth()->id(),
            ]);

            // Regla de negocio: al pagar, pasa a POR_COMPROBAR y guarda fecha_pago
            $requisicion->update([
                'fecha_pago' => $request->input('fecha_pago'),
                'status' => 'POR_COMPROBAR',
            ]);

            return back()->with('success', 'Pago registrado.');
        });
    }

    private function safeNombre($model): string
    {
        if (!$model) return '—';
        foreach (['nombre_completo','nombreCompleto','name','nombre'] as $k) {
            if (!empty($model->{$k})) return (string) $model->{$k};
        }

        $parts = [];
        foreach (['nombre','nombres','apellido_paterno','apellido_materno','apellidoPaterno','apellidoMaterno'] as $k) {
            if (!empty($model->{$k})) $parts[] = $model->{$k};
        }
        $txt = trim(implode(' ', $parts));
        return $txt !== '' ? $txt : '—';
    }

    private function buildBeneficiario(Requisicion $requisicion): array
    {
        // Si hay proveedor, pagas a proveedor. Si no, pagas al solicitante (reembolso).
        if ($requisicion->proveedor) {
            return [
                'nombre' => $requisicion->proveedor->razon_social ?? '—',
                'cuenta' => $requisicion->proveedor->cuenta ?? null, // si no existe, queda null
                'clabe' => $requisicion->proveedor->clabe ?? null,
                'banco' => $requisicion->proveedor->banco ?? null,
            ];
        }

        $s = $requisicion->solicitante;
        return [
            'nombre' => $this->safeNombre($s),
            'cuenta' => $s->cuenta ?? null,
            'clabe' => $s->clabe ?? null,
            'banco' => $s->banco ?? null,
        ];
    }
}
