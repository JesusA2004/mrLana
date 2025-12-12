<?php // app/Models/Pago.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Pago
 *
 * Representa un pago de rendimiento asociado a un contrato de inversión.
 *
 * @property int $id
 * @property int $contrato_id
 * @property string $fecha_pago
 * @property float $rendimiento_bruto
 * @property float $retenciones
 * @property float $rendimiento_neto
 * @property string $status
 * @property string|null $recibo_pago_ruta
 */
class Pago extends Model
{
    
    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // El pago pertenece a un contrato de inversión.
    public function contrato()
    {
        return $this->belongsTo(Contrato::class);
    }

}
