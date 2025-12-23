<?php // app/Models/Requisicion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

/**
 * Class Requisicion
 *
 * Representa la cabecera del flujo de gasto:
 * Requisición -> Detalles -> Comprobantes -> (Gastos/Ajustes/Folios)
 *
 * @property int $id
 * @property string $folio
 * @property string $tipo
 * @property string $status
 * @property int $comprador_corp_id
 * @property int $sucursal_id
 * @property int $solicitante_id
 * @property int|null $proveedor_id
 * @property int $concepto_id
 * @property float $monto_subtotal
 * @property float $monto_iva
 * @property float $monto_total
 * @property string|null $lugar_entrega_texto
 * @property string|null $fecha_entrega
 * @property string $fecha_captura
 * @property string|null $fecha_pago
 */
class Requisicion extends Model
{

    use HasFactory, LogsActivity;

    // Protección contra asignación masiva.
    protected $guarded = ['id'];

    // Cast de fechas y campos especiales.
    protected $casts = [
        'monto_subtotal' => 'decimal:2',
        'monto_total'    => 'decimal:2',
        'fecha_captura'  => 'datetime',
        'fecha_pago'     => 'date',
    ];

    // Corporativo comprador asociado a la requisición.
    public function comprador()
    {
        return $this->belongsTo(Corporativo::class, 'comprador_corp_id');
    }

    // Corporativo beneficiario o dueño del gasto
    public function corporativo()
    {
        return $this->belongsTo(Corporativo::class, 'corporativo_id');
    }

    // Sucursal donde se carga la requisición.
    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class);
    }

    // Empleado que solicita la requisición.
    public function solicitante()
    {
        return $this->belongsTo(Empleado::class, 'solicitante_id');
    }

    // Proveedor asociado.
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Concepto asociado (clasificación contable/operativa).
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

    // -----------------------------
    // Relaciones operativas
    // -----------------------------

    // Renglones de la requisición.
    public function detalles()
    {
        return $this->hasMany(Detalle::class, 'requisicion_id');
    }

    // Comprobantes cargados a la requisición.
    public function comprobantes()
    {
        return $this->hasMany(Comprobante::class, 'requisicion_id');
    }

    // Ajustes aplicados a la requisición
    public function ajustes()
    {
        return $this->hasMany(Ajuste::class, 'requisicion_id');
    }

    // -----------------------------
    // Scopes (útiles para index)
    // -----------------------------

    // Búsqueda rápida por folio o descripción (para el index de Inertia).
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

        // Tabs estilo UI
        if ($tab === 'PENDIENTES') {
            return $query->whereIn('status', ['BORRADOR', 'CAPTURADA', 'POR_COMPROBAR']);
        }

        if ($tab === 'APROBADAS') {
            return $query->whereIn('status', ['ACEPTADA', 'PAGADA', 'COMPROBADA']);
        }

        if ($tab === 'RECHAZADAS') {
            return $query->where('status', 'RECHAZADA');
        }

        // fallback: status exacto
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
