<?php // app/Models/Requisicion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Requisicion
 *
 * Representa una requisición de gasto (anticipo o reembolso), ligada a un corporativo comprador, sucursal, solicitante, proveedor y concepto. Se complementa con detalles, comprobantes, ajustes y posibles gastos asociados.
 *
 * @property int $id
 * @property string $folio
 * @property string $tipo
 * @property string $status
 * @property int $comprador_corp_id
 * @property int $sucursal_id
 * @property int $solicitante_id
 * @property int|null $proveedor_id
 * @property int $concepto_id
 * @property float $monto_subtotal
 * @property float $monto_iva
 * @property float $monto_total
 * @property string|null $lugar_entrega_texto
 * @property string|null $fecha_entrega
 * @property string $fecha_captura
 * @property string|null $fecha_pago
 */
class Requisicion extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Cast de fechas y campos especiales.
    protected $casts = [
        'fecha_captura' => 'datetime',
        'fecha_entrega' => 'date',
        'fecha_pago'    => 'date',
    ];

    // Corporativo comprador asociado a la requisición.
    public function comprador()
    {
        return $this->belongsTo(Corporativo::class, 'comprador_corp_id');
    }

    // Sucursal donde se carga la requisición.
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    // Empleado que solicita la requisición.
    public function solicitante()
    {
        return $this->belongsTo(Empleado::class, 'solicitante_id');
    }

    // Proveedor al que se le pagará (si aplica).
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Concepto general de la requisición.
    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }

    // Usuario que creó la requisición en el sistema.
    public function creador()
    {
        return $this->belongsTo(User::class, 'creada_por_user_id');
    }

    // Detalles (líneas) de la requisición.
    public function detalles()
    {
        return $this->hasMany(Detalle::class);
    }

    // Comprobantes relacionados a esta requisición.
    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class);
    }

    // Ajustes realizados (devolución o faltante).
    public function ajustes()
    {
        return $this->hasMany(Ajuste::class);
    }

    // Gastos generados a partir de esta requisición.
    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }

}
