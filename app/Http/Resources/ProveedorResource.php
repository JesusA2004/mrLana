<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'user_duenio_id' => $this->user_duenio_id,

            'nombre_comercial' => $this->nombre_comercial,
            'rfc' => $this->rfc,
            'email' => $this->email,

            'beneficiario' => $this->beneficiario,
            'banco' => $this->banco,
            'cuenta' => $this->cuenta,
            'clabe' => $this->clabe,

            'estatus' => $this->estatus,

            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }

}
