<?php // app/Models/Ingreso.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Ingreso
 *
 * Representa un ingreso registrado en el sistema, ya sea por ventas, aportes de inversionistas u otros conceptos.
 *
 * @property int $id
 * @property string $fecha_ingreso
 * @property int|null $corporativo_id
 * @property int|null $sucursal_id
 * @property int|null $inversionista_id
 * @property float $monto
 * @property string $moneda
 * @property string $origen
 * @property string|null $descripcion
 */
class Ingreso extends Model
{
    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Cast de la fecha de ingreso.
    protected $casts = [
        'fecha_ingreso' => 'date',
    ];

    // Ingreso asociado a un corporativo.
    public function corporativo()
    {
        return $this->belongsTo(Corporativo::class);
    }

    // Ingreso asociado a una sucursal.
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    // Ingreso asociado a un inversionista (aportes).
    public function inversionista()
    {
        return $this->belongsTo(Inversionista::class);
    }

}
