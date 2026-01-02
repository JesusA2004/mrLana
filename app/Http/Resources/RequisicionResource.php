<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RequisicionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'tipo' => $this->tipo,
            'status' => $this->status,

            'monto_subtotal' => $this->monto_subtotal,
            'monto_total' => $this->monto_total,

            'fecha_captura' => optional($this->fecha_captura)->toISOString(),
            'fecha_pago' => optional($this->fecha_pago)->format('Y-m-d'),

            'observaciones' => $this->observaciones,

            'comprador' => $this->whenLoaded('comprador', fn() => [
                'id' => $this->comprador?->id,
                'nombre' => $this->comprador?->nombre,
            ]),

            'sucursal' => $this->whenLoaded('sucursal', fn() => [
                'id' => $this->sucursal?->id,
                'nombre' => $this->sucursal?->nombre,
                'codigo' => $this->sucursal?->codigo,
                'corporativo_id' => $this->sucursal?->corporativo_id,
                'activo' => $this->sucursal?->activo,
            ]),

            'solicitante' => $this->whenLoaded('solicitante', fn() => [
                'id' => $this->solicitante?->id,
                'nombre' => trim(($this->solicitante?->nombre ?? '').' '.($this->solicitante?->apellido_paterno ?? '').' '.($this->solicitante?->apellido_materno ?? '')),
                'puesto' => $this->solicitante?->puesto,
                'activo' => $this->solicitante?->activo,
            ]),

            'proveedor' => $this->whenLoaded('proveedor', fn() => [
                'id' => $this->proveedor?->id,
                'nombre' => $this->proveedor?->nombre_comercial,
            ]),

            'concepto' => $this->whenLoaded('concepto', fn() => [
                'id' => $this->concepto?->id,
                'nombre' => $this->concepto?->nombre,
                'activo' => $this->concepto?->activo,
            ]),
        ];
    }
    
}
