<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequisicionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sol = $this->whenLoaded('solicitante');
        $suc = $this->whenLoaded('sucursal');
        $cmp = $this->whenLoaded('comprador');
        $con = $this->whenLoaded('concepto');
        $prov = $this->whenLoaded('proveedor');

        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'tipo' => $this->tipo,
            'status' => $this->status,

            'monto_total' => (string) $this->monto_total,
            'monto_subtotal' => (string) $this->monto_subtotal,

            'fecha_captura' => optional($this->fecha_captura)->format('Y-m-d H:i:s'),
            'fecha_pago' => $this->fecha_pago?->format('Y-m-d'),

            'observaciones' => $this->observaciones,

            'comprador' => $cmp ? [
                'id' => $cmp->id,
                'nombre' => $cmp->nombre,
            ] : null,

            'sucursal' => $suc ? [
                'id' => $suc->id,
                'nombre' => $suc->nombre,
            ] : null,

            'solicitante' => $sol ? [
                'id' => $sol->id,
                'nombre' => trim($sol->nombre . ' ' . $sol->apellido_paterno . ' ' . ($sol->apellido_materno ?? '')),
            ] : null,

            'concepto' => $con ? [
                'id' => $con->id,
                'nombre' => $con->nombre,
            ] : null,

            'proveedor' => $prov ? [
                'id' => $prov->id,
                'nombre' => $prov->nombre_comercial,
            ] : null,
        ];
    }
}
