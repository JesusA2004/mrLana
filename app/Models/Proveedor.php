<?php // app/Models/Proveedor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Proveedor
 *
 * Representa un proveedor registrado por un usuario en particular. Incluye datos fiscales y bancarios para pagos de requisiciones y comprobantes.
 *
 * @property int $id
 * @property int $user_duenio_id
 * @property string $nombre_comercial
 * @property string|null $razon_social
 * @property string|null $rfc
 * @property string|null $direccion
 * @property string|null $contacto
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $beneficiario
 * @property string|null $banco
 * @property string|null $cuenta
 * @property string|null $clabe
 */
class Proveedor extends Model
{
    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Usuario dueño de este proveedor (creador).
    public function duenio()
    {
        return $this->belongsTo(User::class, 'user_duenio_id');
    }

    // Requisiciones asociadas a este proveedor.
    public function requisicions()
    {
        return $this->hasMany(Requisicion::class);
    }

    // Comprobantes emitidos por este proveedor.
    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class);
    }

}
