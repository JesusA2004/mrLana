<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PagoResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'fecha_pago' => optional($this->fecha_pago)->format('Y-m-d'),
            'tipo_pago' => $this->tipo_pago,
            'monto' => (float) $this->monto,

            'archivo' => $this->archivo_url ? [
                'label' => $this->archivo_original ?: basename($this->archivo_path ?? ''),
                'url' => $this->archivo_url,
            ] : null,

            'beneficiario' => [
                'nombre' => $this->beneficiario_nombre,
                'cuenta' => $this->cuenta,
                'clabe' => $this->clabe,
                'banco' => $this->banco,
            ],
        ];
    }
}
