<?php // app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Mail\CambioContraseñaEmail;
use Illuminate\Support\Facades\Mail;
use App\Traits\LogsActivity;

/**
 * Class User
 *
 * Representa a un usuario autenticable dentro del sistema. Puede estar asociado a un empleado, tener un rol definido (ADMIN, CONTADOR, COLABORADOR) y relacionarse con proveedores, requisiciones, comprobantes, ajustes y folios registrados.
 *
 * Este modelo conserva completamente las configuraciones originales de Laravel Breeze y agrega las relaciones necesarias para el ERP de gastos (Filament)
 *
 * @property int $id
 * @property int|null $empleado_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $rol
 * @property bool $activo
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;

    /**
     * Atributos con asignación masiva permitida.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atributos ocultos al serializar.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casts de atributos especiales.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*=========================================================
     | RELACIONES DEL ERP
     =========================================================*/

    /**
     * Relación: Usuario → Empleado (opcional).
     * Un usuario puede estar ligado al registro de un empleado.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Proveedores creados por este usuario.
     */
    public function proveedors()
    {
        return $this->hasMany(Proveedor::class, 'user_duenio_id');
    }

    /**
     * Requisiciones creadas por este usuario.
     */
    public function requisicionsCreadas()
    {
        return $this->hasMany(Requisicion::class, 'creada_por_user_id');
    }

    /**
     * Comprobantes cargados al sistema por este usuario.
     */
    public function comprobantesCargados()
    {
        return $this->hasMany(Comprobante::class, 'user_carga_id');
    }

    /**
     * Ajustes (devolución/faltante) registrados por este usuario.
     */
    public function ajustesRegistrados()
    {
        return $this->hasMany(Ajuste::class, 'user_registro_id');
    }

    /**
     * Folios de factura registrados por este usuario.
     */
    public function foliosRegistrados()
    {
        return $this->hasMany(Folio::class, 'user_registro_id');
    }

}
