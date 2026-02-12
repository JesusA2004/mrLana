<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComprobanteResource extends JsonResource {

    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'fecha_emision' => $this->fecha_emision?->format('Y-m-d'),
            'tipo_doc' => $this->tipo_doc,
            'monto' => (float) $this->monto,
            'archivo' => $this->archivo_url ? [
                'label' => $this->archivo_original ?: basename($this->archivo_path ?? ''),
                'url' => $this->archivo_url,
            ] : null,
            'estatus' => $this->estatus,
            'comentario_revision' => $this->comentario_revision,
            'revisado_at' => $this->revisado_at?->toISOString(),
            'user_carga' => $this->userCarga ? [
                'id' => $this->userCarga->id,
                'name' => $this->userCarga->name,
            ] : null,
            'user_revision' => $this->userRevision ? [
                'id' => $this->userRevision->id,
                'name' => $this->userRevision->name,
            ] : null,
        ];
    }

}
