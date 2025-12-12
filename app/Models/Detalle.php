<?php // app/Models/Detalle.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Detalle
 *
 * Representa una línea dentro de una requisición, con información de cantidad, descripción, montos y sucursal asociada.
 *
 * @property int $id
 * @property int $requisicion_id
 * @property int|null $sucursal_id
 * @property float $cantidad
 * @property string $descripcion
 * @property float $precio_unitario
 * @property float $subtotal
 * @property float $iva
 * @property float $total
 */
class Detalle extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // El detalle pertenece a una requisición.
    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class);
    }

    // Sucursal a la que se asigna este detalle (si aplica).
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

}
