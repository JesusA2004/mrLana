<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Pago extends Model {

    use HasFactory, LogsActivity;

    protected $guarded = ['id'];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    protected $appends = [
        'archivo_url',
    ];

    public function requisicion() {
        return $this->belongsTo(Requisicion::class);
    }

    public function userCarga() {
        return $this->belongsTo(User::class, 'user_carga_id');
    }

    public function getArchivoUrlAttribute(): ?string {
        if (!$this->archivo_path) return null;
        return Storage::url($this->archivo_path);
    }

}
