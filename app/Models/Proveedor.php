<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabla: proveedors
 *
 * @property int $id
 * @property int $user_duenio_id
 * @property string $razon_social
 * @property string $rfc
 * @property string $clabe
 * @property string $banco
 * @property string status;
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Proveedor extends Model {

    use HasFactory, LogsActivity;

    protected $table = 'proveedors';

    // Solo protegemos el id; lo demás se puede asignar masivamente.
    protected $guarded = ['id'];

    protected $casts = [
        'user_duenio_id' => 'integer',
    ];

    //  Relación: usuario dueño (creador) del proveedor.
    public function duenio(): BelongsTo {
        return $this->belongsTo(User::class, 'user_duenio_id');
    }

    // Relación: requisiciones asociadas (si tu Requisicion tiene proveedor_id).
    public function requisicions(): HasMany {
        return $this->hasMany(Requisicion::class, 'proveedor_id');
    }

    // Scope: filtra por dueño (multi-tenant simple).
    public function scopeOwnedBy($query, int $userId) {
        return $query->where('user_duenio_id', $userId);
    }

}
