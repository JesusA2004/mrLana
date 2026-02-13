<?php // app/Models/Folio.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Folio extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Usuario que registró este folio en el sistema.
    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'user_registro_id');
    }

}
