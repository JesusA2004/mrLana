<?php // app/Models/Area.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Area
 *
 * Representa un área o departamento dentro de un corporativo, a la cual se asocian empleados.
 *
 * @property int $id
 * @property int|null $corporativo_id
 * @property string $nombre
 * @property bool $activo
 */
class Area extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    protected $fillable = [
        'corporativo_id',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // El área pertenece a un corporativo.
    public function corporativo()
    {
        return $this->belongsTo(Corporativo::class);
    }

    // Un área agrupa múltiples empleados.
    public function empleados()
    {
        return $this->hasMany(Empleado::class);
    }

}
