<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Requisicion extends Model
{
    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'monto_subtotal' => 'decimal:2',
        'monto_total'    => 'decimal:2',
        'fecha_captura'  => 'datetime',
        'fecha_pago'     => 'date',
    ];

    // =========================
    // Relaciones
    // =========================

    public function comprador()
    {
        return $this->belongsTo(Corporativo::class, 'comprador_corp_id');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function solicitante()
    {
        return $this->belongsTo(Empleado::class, 'solicitante_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function concepto()
    {
        return $this->belongsTo(Concepto::class);
    }

    public function recurrencia()
    {
        return $this->belongsTo(RequisicionRecurrencia::class, 'recurrencia_id');
    }

    public function creadaPor()
    {
        return $this->belongsTo(User::class, 'creada_por_user_id');
    }

    public function detalles()
    {
        return $this->hasMany(Detalle::class, 'requisicion_id');
    }

    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'requisicion_id');
    }

    public function ajustes()
    {
        return $this->hasMany(Ajuste::class, 'requisicion_id');
    }

    // =========================
    // Scopes
    // =========================

    public function scopeSearch($query, ?string $q)
    {
        $q = trim((string) $q);
        if ($q === '') return $query;

        return $query->where(function ($sub) use ($q) {
            $sub->where('folio', 'like', "%{$q}%")
                ->orWhere('observaciones', 'like', "%{$q}%");
        });
    }

    public function scopeStatusTab($query, ?string $tab)
    {
        $tab = strtoupper(trim((string) $tab));
        if ($tab === '' || $tab === 'TODAS') return $query;

        if ($tab === 'PENDIENTES') {
            return $query->whereIn('status', ['BORRADOR', 'CAPTURADA', 'POR_COMPROBAR']);
        }

        if ($tab === 'APROBADAS') {
            return $query->whereIn('status', ['ACEPTADA', 'PAGADA', 'COMPROBADA']);
        }

        if ($tab === 'RECHAZADAS') {
            return $query->where('status', 'RECHAZADA');
        }

        return $query->where('status', $tab);
    }

    public function scopeDateRangeCaptura($query, ?string $from, ?string $to)
    {
        $from = trim((string) $from);
        $to   = trim((string) $to);

        if ($from !== '') $query->whereDate('fecha_captura', '>=', $from);
        if ($to !== '')   $query->whereDate('fecha_captura', '<=', $to);

        return $query;
    }
    
}
