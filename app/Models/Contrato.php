<?php // app/Models/Contrato.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Contrato
 *
 * Representa un contrato de inversión firmado con un inversionista, ligado a un corporativo y con múltiples pagos de rendimiento.
 *
 * @property int $id
 * @property int $inversionista_id
 * @property int|null $corporativo_id
 * @property string $no_contrato
 * @property string $fecha_contrato
 * @property float $capital_inicial
 * @property string $fecha_reembolso
 * @property int $plazo_meses
 * @property float $tasa_anual
 * @property float $tasa_mensual
 * @property string $banco
 * @property string $clabe
 * @property string $cuenta
 * @property float $rendimiento_bruto_mensual
 * @property float $retencion_mensual
 * @property float $rendimiento_neto_mensual
 * @property string $periodicidad_pago
 * @property int $dia_pago
 * @property string $status
 */
class Contrato extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // El contrato pertenece a un inversionista.
    public function inversionista()
    {
        return $this->belongsTo(Inversionista::class);
    }

    // El contrato puede estar asociado a un corporativo.
    public function corporativo()
    {
        return $this->belongsTo(Corporativo::class);
    }

    // Pagos de rendimiento generados por este contrato.
    public function pagos()
    {
        return $this->hasMany(Pago::class);
    }

}
