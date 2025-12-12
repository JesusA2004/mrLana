<?php // app/Models/Inversionista.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Inversionista
 *
 * Representa a una persona o entidad que realiza inversiones en la empresa, con uno o varios contratos de inversión asociados.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $rfc
 * @property string|null $direccion
 * @property string|null $telefono
 * @property string|null $email
 */
class Inversionista extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Contratos de inversión del inversionista.
    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    // Ingresos asociados al inversionista (aportes).
    public function ingresos()
    {
        return $this->hasMany(Ingreso::class);
    }

}
