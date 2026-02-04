<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso PlantillaResource
 *
 * Formatea una plantilla para enviarla al frontend (por ejemplo, para listar o precargar en la creación de una requisición).
 */
class PlantillaResource extends JsonResource {

    public function toArray($request): array {
        return [
            'id'       => $this->id,
            'nombre'   => $this->nombre,
            'status'   => $this->status,
            'monto_subtotal' => $this->monto_subtotal,
            'monto_total'    => $this->monto_total,
            'fecha_solicitud'    => optional($this->fecha_solicitud)->toISOString(),
            'fecha_autorizacion' => optional($this->fecha_autorizacion)->toISOString(),
            'observaciones' => $this->observaciones,
            'sucursal' => $this->whenLoaded('sucursal', fn() => [
                'id'     => $this->sucursal?->id,
                'nombre' => $this->sucursal?->nombre,
                'codigo' => $this->sucursal?->codigo,
            ]),
            'solicitante' => $this->whenLoaded('solicitante', fn() => [
                'id'     => $this->solicitante?->id,
                'nombre' => trim(($this->solicitante?->nombre ?? '').' '.($this->solicitante?->apellido_paterno ?? '').' '.($this->solicitante?->apellido_materno ?? '')),
            ]),
            'proveedor' => $this->whenLoaded('proveedor', fn() => [
                'id'     => $this->proveedor?->id,
                'nombre' => $this->proveedor?->razon_social,
            ]),
            'concepto' => $this->whenLoaded('concepto', fn() => [
                'id'     => $this->concepto?->id,
                'nombre' => $this->concepto?->nombre,
            ]),
            'detalles' => $this->whenLoaded('detalles', fn() => $this->detalles->map(function ($d) {
                return [
                    'id'             => $d->id,
                    'sucursal_id'    => $d->sucursal_id,
                    'sucursal'       => $d->sucursal?->nombre,
                    'cantidad'       => $d->cantidad,
                    'descripcion'    => $d->descripcion,
                    'precio_unitario'=> $d->precio_unitario,
                    'subtotal'       => $d->subtotal,
                    'iva'            => $d->iva,
                    'total'          => $d->total,
                ];
            })),
        ];
    }

}
