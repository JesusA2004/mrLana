<?php // app/Models/Comprobante.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Comprobante extends Model {

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Cast de fechas relevantes.
    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_carga'   => 'datetime',
    ];

    // El comprobante pertenece a una requisición.
    public function requisicion() {
        return $this->belongsTo(Requisicion::class);
    }

    // Proveedor emisor del comprobante.
    public function proveedor() {
        return $this->belongsTo(Proveedor::class);
    }

    // Usuario que cargó el comprobante al sistema.
    public function userCarga() {
        return $this->belongsTo(User::class, 'user_carga_id');
    }

}
