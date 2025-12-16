<?php // app/Models/Concepto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Concepto
 *
 * Catálogo de conceptos de requisición (gasolina, viáticos, servicios, etc.).
 *
 * @property int $id
 * @property string $grupo
 * @property string $nombre
 * @property bool $activo
 */
class Concepto extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    protected $fillable = [
        'grupo',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Requisiciones asociadas a este concepto.
    public function requisicions()
    {
        return $this->hasMany(Requisicion::class, 'concepto_id');
    }

}
