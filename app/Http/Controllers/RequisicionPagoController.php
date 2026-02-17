<?php

namespace App\Http\Controllers;

use App\Http\Requests\Pagos\StorePagoRequest;
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

        // Nota: NO dependo de PagoResource para no romper shape del front.
        // Mantengo exactamente lo que Vue consume: archivo {label,url} | null
        $pagosShape = $pagos->map(function ($p) {
            $url = null;
            if (!empty($p->archivo_path)) {
                $url = Storage::disk('public')->url($p->archivo_path);
            }

            return [
                'id' => (int) $p->id,
                'fecha_pago' => $p->fecha_pago,
                'tipo_pago' => (string) ($p->tipo_pago ?? ''),
                'monto' => (float) ($p->monto ?? 0),
                'referencia' => $p->referencia ?? null,
                'archivo' => $url ? [
                    'label' => $p->archivo_original ?: 'Ver archivo',
                    'url' => $url,
                ] : null,
                'beneficiario' => [
                    'nombre' => $p->beneficiario_nombre ?? null,
                    'rfc' => $p->rfc ?? null,
                    'clabe' => $p->clabe ?? null,
                    'banco' => $p->banco ?? null,
                ],
            ];
        })->values();

        return Inertia::render('Requisiciones/Pagar', [
            'requisicion' => [
                'data' => [
                    'id' => $requisicion->id,
                    'folio' => $requisicion->folio,
                    'concepto' => $requisicion->concepto?->nombre ?? null,
                    'monto_total' => (float) $requisicion->monto_total,
                    'solicitante_nombre' => $this->safeNombre($requisicion->solicitante),
                    'beneficiario' => $benef,
                    'status' => (string) ($requisicion->status ?? ''),
                ],
            ],
            'pagos' => [
                'data' => $pagosShape,
            ],
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

    public function store(StorePagoRequest $request, Requisicion $requisicion)
    {
        $requisicion->load(['proveedor', 'solicitante']);

        // Nota: aquí protejo la regla de negocio clave: NO pagar de más.
        // Y mantengo consistencia con Comprobantes: si pendiente=0, solo acepto 0.00.
        return DB::transaction(function () use ($request, $requisicion) {
            $pagado = (float) $requisicion->pagos()->sum('monto');
            $pendiente = max(0, (float) $requisicion->monto_total - $pagado);

            $monto = round((float) $request->input('monto'), 2);

            if ($pendiente > 0 && $monto > ($pendiente + 0.00001)) {
                return back()->withErrors([
                    'monto' => "El monto ($monto) excede lo pendiente ($pendiente).",
                ]);
            }

            if ($pendiente <= 0 && abs($monto) > 0.00001) {
                return back()->withErrors([
                    'monto' => "Pendiente en 0. Solo se permite monto 0.00.",
                ]);
            }

            $file = $request->file('archivo');

            // Nota: guardo en /requisiciones/{id}/pagos para que todo quede ordenado por requisición.
            $folder = "requisiciones/{$requisicion->id}/pagos";
            $stored = null;

            try {
                $stored = $file->storePublicly($folder, 'public');

                $benef = $this->buildBeneficiario($requisicion);

                // Nota: uso forceFill para no depender de $fillable (me evita “se guardó en 0” o campos ignorados).
                (new Pago())->forceFill([
                    'requisicion_id' => $requisicion->id,
                    'beneficiario_nombre' => $benef['nombre'] ?? '—',
                    'rfc' => $benef['rfc'] ?? null,
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
                ])->save();

                // Regla de negocio: al registrar pago, la requisición pasa a POR_COMPROBAR.
                $requisicion->update([
                    'fecha_pago' => $request->input('fecha_pago'),
                    'status' => 'POR_COMPROBAR',
                ]);

                return back()->with('success', 'Pago registrado.');
            } catch (\Throwable $e) {
                // Nota: si algo revienta después de guardar archivo, lo limpio para no dejar basura en storage.
                if ($stored) {
                    Storage::disk('public')->delete($stored);
                }
                throw $e;
            }
        });
    }

    private function safeNombre($model): string
    {
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

    private function buildBeneficiario(Requisicion $requisicion): array
    {
        // Nota: si hay proveedor, pagamos a proveedor. Si no, es reembolso al solicitante.
        if ($requisicion->proveedor) {
            return [
                'nombre' => $requisicion->proveedor->razon_social ?? '—',
                'rfc' => $requisicion->proveedor->rfc ?? null,
                'clabe' => $requisicion->proveedor->clabe ?? null,
                'banco' => $requisicion->proveedor->banco ?? null,
            ];
        }

        $s = $requisicion->solicitante;
        return [
            'nombre' => $this->safeNombre($s),
            'rfc' => $s->rfc ?? null,
            'clabe' => $s->clabe ?? null,
            'banco' => $s->banco ?? null,
        ];
    }
}
