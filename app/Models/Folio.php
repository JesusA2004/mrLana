<?php // app/Models/Folio.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Folio
 *
 * Representa un folio de factura o comprobante registrado para evitar duplicidades. Puede ser capturado manualmente o por el sistema.
 *
 * @property int $id
 * @property string $folio
 * @property string|null $rfc_emisor
 * @property string|null $rfc_receptor
 * @property float|null $monto_total
 * @property string $origen
 * @property int $user_registro_id
 */
class Folio extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Usuario que registró este folio en el sistema.
    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'user_registro_id');
    }

}
