<?php

namespace App\Traits;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Auto-hook Eloquent events:
     * - created => CREACION
     * - updated => ACTUALIZACION
     * - deleted => ELIMINACION
     *
     * Esto garantiza log en cada acción sin depender del controller.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('CREACION');
        });

        static::updated(function ($model) {
            $model->logActivity('ACTUALIZACION');
        });

        static::deleted(function ($model) {
            $model->logActivity('ELIMINACION');
        });
    }

    /**
     * Registra una acción en system_logs.
     *
     * @param string $accion  Ej: "CREACION", "ACTUALIZACION", "ELIMINACION"
     * @param string|null $descripcionExtra  Texto adicional opcional
     */
    public function logActivity(string $accion, string $descripcionExtra = null)
    {
        $user = Auth::user();

        $tabla = $this->getTable();
        $registroId = $this->id ?? null;

        $fecha = now()->format('d/m/Y');
        $hora  = now()->format('H:i:s');

        $descripcion = sprintf(
            'El usuario con id %d (%s) realizó una %s en la tabla "%s" sobre el registro %s el %s a las %s desde %s.',
            $user?->id ?? 0,
            $user?->name ?? 'SIN NOMBRE',
            $accion,
            $tabla,
            $registroId ?? 'N/A',
            $fecha,
            $hora,
            Request::ip()
        );

        if ($descripcionExtra) {
            $descripcion .= ' ' . $descripcionExtra;
        }

        SystemLog::create([
            'user_id'     => $user?->id,
            'accion'      => $accion,
            'tabla'       => $tabla,
            'registro_id' => $registroId,
            'ip_address'  => Request::ip(),
            'user_agent'  => Request::userAgent(),
            'descripcion' => $descripcion,
        ]);
    }

}
