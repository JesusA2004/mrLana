<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource: RequisicionResource
 *
 * No es "API". Es un transformador para props de Inertia:
 * - Te deja el front limpio (sin lógica de armado de strings)
 * - Controlas exactamente qué campos y relaciones mandas
 */
class RequisicionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'            => $this->id,
            'folio_unico'   => $this->folio_unico,
            'tipo'          => $this->tipo,
            'descripcion'   => $this->descripcion,
            'monto_total'   => (string) $this->monto_total,
            'moneda'        => $this->moneda,
            'status'        => $this->status,
            'fecha_captura' => optional($this->fecha_captura)->format('Y-m-d'),

            // FKs para editar (selects)
            'corporativo_id' => $this->corporativo_id,
            'sucursal_id'    => $this->sucursal_id,
            'solicitante_id' => $this->solicitante_id,
            'proveedor_id'   => $this->proveedor_id,
            'concepto_id'    => $this->concepto_id,

            // Relaciones mínimas para pintar en tabla
            'sucursal' => $this->whenLoaded('sucursal', fn () => [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
            ]),
            'solicitante' => $this->whenLoaded('solicitante', fn () => [
                'id' => $this->solicitante->id,
                'nombre_completo' => trim(
                    ($this->solicitante->nombre ?? '') . ' ' . ($this->solicitante->apellido_paterno ?? '')
                ),
            ]),
            'proveedor' => $this->whenLoaded('proveedor', fn () => [
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
            ]),
            'concepto' => $this->whenLoaded('concepto', fn () => [
                'id' => $this->concepto->id,
                'nombre' => $this->concepto->nombre,
            ]),
        ];
    }
}
