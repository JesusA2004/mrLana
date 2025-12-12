<?php // app/Models/Sucursal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Sucursal
 *
 * Representa una sucursal física o administrativa ligada a un corporativo.
 * En ella se concentran empleados, requisiciones, ingresos y gastos.
 *
 * @property int $id
 * @property int $corporativo_id
 * @property string $nombre
 * @property string|null $codigo
 * @property string|null $ciudad
 * @property string|null $estado
 * @property string|null $direccion
 * @property bool $activo
 */
class Sucursal extends Model
{
    
    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // La sucursal pertenece a un corporativo.
    public function corporativo()
    {
        return $this->belongsTo(Corporativo::class);
    }

    // Una sucursal tiene múltiples empleados.
    public function empleados()
    {
        return $this->hasMany(Empleado::class);
    }

    // Requisiciones asociadas a esta sucursal.
    public function requisicions()
    {
        return $this->hasMany(Requisicion::class);
    }

    // Detalles de requisición que se cargan a esta sucursal.
    public function detalles()
    {
        return $this->hasMany(Detalle::class);
    }

    // Ingresos registrados para esta sucursal.
    public function ingresos()
    {
        return $this->hasMany(Ingreso::class);
    }

    // Gastos registrados para esta sucursal.
    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }

}
