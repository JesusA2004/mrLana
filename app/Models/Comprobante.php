<?php // app/Models/Comprobante.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Comprobante
 *
 * Representa un comprobante fiscal o documento (factura, ticket, nota) asociado a una requisición, con datos fiscales y montos.
 *
 * @property int $id
 * @property int $requisicion_id
 * @property int|null $proveedor_id
 * @property string $tipo_doc
 * @property string|null $uuid_cfdi
 * @property string|null $folio
 * @property string|null $rfc_emisor
 * @property string|null $rfc_receptor
 * @property float $subtotal
 * @property float $iva
 * @property float $total
 * @property string $estatus
 * @property string|null $fecha_emision
 * @property string $fecha_carga
 * @property int $user_carga_id
 */
class Comprobante extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Cast de fechas relevantes.
    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_carga'   => 'datetime',
    ];

    // El comprobante pertenece a una requisición.
    public function requisicion()
    {
        return $this->belongsTo(Requisicion::class);
    }

    // Proveedor emisor del comprobante.
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Usuario que cargó el comprobante al sistema.
    public function userCarga()
    {
        return $this->belongsTo(User::class, 'user_carga_id');
    }

}
