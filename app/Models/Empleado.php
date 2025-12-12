<?php // app/Models/Empleado.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Empleado
 *
 * Representa a un empleado ligado a una sucursal y área. Puede tener usuario de sistema, generar requisiciones y estar asociado a gastos.
 *
 * @property int $id
 * @property int $sucursal_id
 * @property int|null $area_id
 * @property string $nombre
 * @property string $apellido_paterno
 * @property string|null $apellido_materno
 * @property string|null $email
 * @property string|null $telefono
 * @property string|null $puesto
 * @property bool $activo
 */
class Empleado extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // El empleado pertenece a una sucursal.
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    // El empleado pertenece a un área.
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    // Relación uno a uno con el usuario del sistema.
    public function user()
    {
        return $this->hasOne(User::class);
    }

    // Requisiciones donde el empleado es el solicitante.
    public function requisicionsSolicitadas()
    {
        return $this->hasMany(Requisicion::class, 'solicitante_id');
    }

    // Gastos asociados al empleado (responsable).
    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }

}
