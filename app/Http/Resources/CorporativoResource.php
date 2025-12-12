<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CorporativoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'rfc'       => $this->rfc,
            'direccion' => $this->direccion,
            'telefono'  => $this->telefono,
            'email'     => $this->email,
            'codigo'    => $this->codigo,
            'logo_path' => $this->logo_path,
            'activo'    => (bool) $this->activo,
            'created_at'=> optional($this->created_at)->toDateTimeString(),
            'updated_at'=> optional($this->updated_at)->toDateTimeString(),
        ];
    }
}
