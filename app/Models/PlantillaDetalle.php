<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Modelo PlantillaDetalle
 *
 * Representa una línea dentro de una plantilla. Almacena cantidad, descripción y montos.
 * Similar al modelo Detalle de las requisiciones pero apuntando a una plantilla.
 */
class PlantillaDetalle extends Model {

    use HasFactory, LogsActivity;

    // Campos protegidos contra asignación masiva
    protected $guarded = ['id'];

    // Relación hacia la plantilla que pertenece
    public function plantilla() {
        return $this->belongsTo(Plantilla::class);
    }

    // Relación opcional a la sucursal (si se quiere distinguir diferentes sucursales)
    public function sucursal() {
        return $this->belongsTo(Sucursal::class);
    }

}
