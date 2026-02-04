<?php // app/Models/Ajuste.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Ajuste extends Model {

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Cast de fecha de registro.
    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    // Ajuste ligado a una requisición.
    public function requisicion() {
        return $this->belongsTo(Requisicion::class);
    }

    // Usuario que registró el ajuste.
    public function usuarioRegistro() {
        return $this->belongsTo(User::class, 'user_registro_id');
    }

}
