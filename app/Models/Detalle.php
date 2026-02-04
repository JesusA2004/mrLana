<?php // app/Models/Detalle.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Detalle extends Model {

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // El detalle pertenece a una requisición.
    public function requisicion() {
        return $this->belongsTo(Requisicion::class);
    }

    // Sucursal a la que se asigna este detalle (si aplica).
    public function sucursal() {
        return $this->belongsTo(Sucursal::class);
    }

}
