<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpleadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'sucursal_id'       => $this->sucursal_id,
            'area_id'           => $this->area_id,
            'nombre'            => $this->nombre,
            'apellido_paterno'  => $this->apellido_paterno,
            'apellido_materno'  => $this->apellido_materno,
            'email'             => $this->email,
            'telefono'          => $this->telefono,
            'puesto'            => $this->puesto,
            'activo'            => (bool) $this->activo,
            'created_at'        => optional($this->created_at)->toISOString(),
            'updated_at'        => optional($this->updated_at)->toISOString(),

            'sucursal' => $this->whenLoaded('sucursal', fn () => [
                'id' => $this->sucursal->id,
                'corporativo_id' => $this->sucursal->corporativo_id ?? null,
                'nombre' => $this->sucursal->nombre,
                'codigo' => $this->sucursal->codigo ?? null,
                'activo' => (bool) ($this->sucursal->activo ?? true),
                'corporativo' => $this->sucursal->relationLoaded('corporativo') && $this->sucursal->corporativo
                    ? [
                        'id' => $this->sucursal->corporativo->id,
                        'nombre' => $this->sucursal->corporativo->nombre,
                        'codigo' => $this->sucursal->corporativo->codigo ?? null,
                        'activo' => (bool) ($this->sucursal->corporativo->activo ?? true),
                    ]
                    : null,
            ]),

            'area' => $this->whenLoaded('area', fn () => $this->area
                ? [
                    'id' => $this->area->id,
                    'corporativo_id' => $this->area->corporativo_id ?? null,
                    'nombre' => $this->area->nombre,
                    'activo' => (bool) ($this->area->activo ?? true),
                  ]
                : null
            ),
        ];
    }
    
}
