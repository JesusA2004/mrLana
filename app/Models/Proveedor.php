<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Proveedor
 *
 * Proveedor registrado por un usuario (dueño). Contiene datos fiscales/bancarios
 * usados para pagos en requisiciones y comprobantes.
 *
 * Tabla: proveedors
 *
 * @property int $id
 * @property int $user_duenio_id
 * @property string $nombre_comercial
 * @property string|null $rfc
 * @property string|null $email
 * @property string|null $beneficiario
 * @property string|null $banco
 * @property string|null $cuenta
 * @property string|null $clabe
 * @property string|null $estatus
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */

class Proveedor extends Model {

    use HasFactory, LogsActivity;

    protected $table = 'proveedors';

    /**
     * Usamos guarded para proteger id. Todo lo demás puede asignarse masivamente.
     */
    protected $guarded = ['id'];

    /**
     * Casts básicos (por si luego estandarizas estatus, etc.)
     */
    protected $casts = [
        'user_duenio_id' => 'integer',
    ];

    /**
     * Estatus sugeridos (no obligatorio, pero ayuda a estandarizar).
     */
    public const ESTATUS_ACTIVO = 'ACTIVO';
    public const ESTATUS_INACTIVO = 'INACTIVO';

    /**
     * Relación: usuario dueño (creador) del proveedor.
     */
    public function duenio(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_duenio_id');
    }

    /**
     * Relación: requisiciones asociadas (si tu Requisicion tiene proveedor_id).
     */
    public function requisicions(): HasMany
    {
        return $this->hasMany(Requisicion::class, 'proveedor_id');
    }

    /**
     * Scope: filtra por dueño (multi-tenant simple).
     */
    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('user_duenio_id', $userId);
    }

}
