<?php // app/Models/Gasto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Gasto
 *
 * Representa un gasto registrado en el sistema, asociado a un corporativo, sucursal, empleado, proveedor, requisición y comprobante.
 *
 * @property int $id
 * @property string $fecha_gasto
 * @property int|null $corporativo_id
 * @property int|null $sucursal_id
 * @property int|null $empleado_id
 * @property int|null $proveedor_id
 * @property float $monto
 * @property string $moneda
 * @property string $tipo_gasto
 * @property string $metodo_pago
 * @property string $estatus_validacion
 * @property int|null $requisicion_id
 * @property int|null $comprobante_id
 * @property string|null $descripcion
 */
class Gasto extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Cast de la fecha de gasto.
    protected $casts = [
        'fecha_gasto' => 'date',
    ];

    // Gasto asociado a un corporativo.
    public function corporativo()
    {
        return $this->belongsTo(Corporativo::class);
    }

    // Gasto asociado a una sucursal.
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    // Gasto asociado a un empleado responsable.
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    // Gasto asociado a un proveedor.
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Gasto vinculado a una requisición.
    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class);
    }

    // Gasto vinculado a un comprobante.
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class);
    }

}
