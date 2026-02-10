<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'user_duenio_id' => $this->user_duenio_id,
            'razon_social' => $this->razon_social,
            'rfc' => $this->rfc,
            'banco' => $this->banco,
            'clabe' => $this->clabe,
            'status' => $this->status,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }

}
