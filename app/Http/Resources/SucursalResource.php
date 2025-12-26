<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SucursalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'nombre'         => $this->nombre,
            'codigo'         => $this->codigo,
            'ciudad'         => $this->ciudad,
            'estado'         => $this->estado,
            'direccion'      => $this->direccion,
            'activo'         => (bool) $this->activo,

            'corporativo_id' => $this->corporativo_id,

            // Útil para tablas
            'corporativo_nombre' => $this->corporativo?->nombre,

            'corporativo' => $this->whenLoaded('corporativo', fn () => [
                'id'     => $this->corporativo->id,
                'nombre' => $this->corporativo->nombre,
                'codigo' => $this->corporativo->codigo,
                'activo' => (bool) $this->corporativo->activo,
            ]),

            // Bandera para la UI (activar sucursal)
            'corporativo_activo' => (bool) ($this->corporativo?->activo ?? false),
            'created_at'=> optional($this->created_at)->toDateTimeString(),
            'updated_at'=> optional($this->updated_at)->toDateTimeString(),
        ];
    }

}
