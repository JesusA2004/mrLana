<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequisicionRecurrencia extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'requisicion_recurrencias';

    protected $guarded = ['id'];

    protected $casts = [
        'activo'            => 'boolean',
        'intervalo'         => 'integer',
        'dia_semana'        => 'integer',
        'dia_mes'           => 'integer',
        'hora_ejecucion'    => 'datetime:H:i:s',
        'proxima_ejecucion' => 'datetime',
        'ultima_generacion' => 'datetime',
        'monto_subtotal'    => 'decimal:2',
        'monto_total'       => 'decimal:2',
        'fecha_aprobacion'  => 'datetime',
    ];

    // =========================
    // Relaciones
    // =========================

    public function solicitante()
    {
        return $this->belongsTo(Empleado::class, 'solicitante_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function comprador()
    {
        return $this->belongsTo(Corporativo::class, 'comprador_corp_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }

    public function creadaPor()
    {
        return $this->belongsTo(User::class, 'creada_por_user_id');
    }

    public function aprobadaPor()
    {
        return $this->belongsTo(User::class, 'aprobada_por_user_id');
    }

    // Una recurrencia genera muchas requisiciones reales
    public function requisiciones()
    {
        return $this->hasMany(Requisicion::class, 'recurrencia_id');
    }

    // =========================
    // Scopes útiles
    // =========================

    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopePendientesDeEjecucion($query)
    {
        return $query
            ->where('activo', true)
            ->where('status', 'APROBADA')
            ->whereNotNull('proxima_ejecucion')
            ->where('proxima_ejecucion', '<=', now());
    }
    
}
