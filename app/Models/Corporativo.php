<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\LogsActivity;

/**
 * Class Corporativo
 *
 * Representa un corporativo dentro del ERP. Un corporativo puede tener múltiples sucursales, áreas, contratos de inversión, ingresos, gastos y requisiciones donde actúa como comprador.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $rfc
 * @property string|null $direccion
 * @property string|null $telefono
 * @property string|null $email
 * @property string|null $codigo
 * @property string|null $logo_path
 * @property bool $activo
*/

class Corporativo extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    protected $fillable = [
        'nombre',
        'rfc',
        'direccion',
        'telefono',
        'email',
        'codigo',
        'logo_path',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // Un corporativo tiene muchas sucursales.
    public function sucursales()
    {
        return $this->hasMany(Sucursal::class);
    }

    // Un corporativo puede tener muchas áreas.
    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    // Un corporativo tiene múltiples contratos de inversión.
    public function contratos()
    {
        return $this->hasMany(Contrato::class);
    }

    // Un corporativo registra múltiples ingresos.
    public function ingresos()
    {
        return $this->hasMany(Ingreso::class);
    }

    // Un corporativo registra múltiples gastos.
    public function gastos()
    {
        return $this->hasMany(Gasto::class);
    }

    // Requisiciones donde este corporativo actúa como comprador.
    public function requisicionesComprador()
    {
        return $this->hasMany(Requisicion::class, 'comprador_corp_id');
    }

}
