<?php

namespace App\Traits;

use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * ==========================================================
 * LogsActivity
 * ----------------------------------------------------------
 * Registra eventos CRUD en system_logs con una descripción útil:
 * - CREATE: describe creación
 * - UPDATE: detecta campos cambiados y arma resumen
 * - Si cambia "activo":
 *    - activo => false  => accion = "ELIMINACION" (baja lógica)
 *    - activo => true   => accion = "ACTIVACION"
 * - DELETE (si llegara a pasar): accion = "ELIMINACION"
 * ==========================================================
 */
trait LogsActivity {

    /**
     * Campos que NO queremos loguear como “cambios”.
     */
    protected array $logIgnoreFields = [
        'updated_at',
        'created_at',
        'deleted_at',
        'remember_token',
        'password',
        'current_jti',
    ];

    /**
     * Máximo de caracteres del detalle para evitar logs gigantes.
     */
    protected int $logMaxLen = 2000;

    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            $model->writeSystemLog('CREACION', $model->buildCreateDescription());
        });

        static::updated(function (Model $model) {
            [$accion, $desc] = $model->buildUpdateActionAndDescription();
            $model->writeSystemLog($accion, $desc);
        });

        static::deleted(function (Model $model) {
            // Si algún módulo llega a usar delete real, lo registramos.
            $model->writeSystemLog('ELIMINACION', 'Eliminación física del registro.');
        });
    }

    /**
     * Construye descripción para creación.
     */
    protected function buildCreateDescription(): string {
        $name = $this->logEntityName();
        return "Registro creado: {$name}.";
    }

    /**
     * Construye acción + descripción para actualización.
     * - Detecta cambios reales con getDirty()
     * - Si solo cambia "activo": se clasifica como eliminación/activación
     * - Si cambia activo + otros: prioriza ELIMINACION/ACTIVACION y agrega detalle de los otros campos
     *
     * @return array{0:string,1:string}
     */
    protected function buildUpdateActionAndDescription(): array {
        $dirty = $this->getDirty();

        // Filtra campos ignorados
        foreach ($this->logIgnoreFields as $f) {
            unset($dirty[$f]);
        }

        // Si no hay cambios relevantes, no logueamos ruido.
        if (empty($dirty)) {
            return ['ACTUALIZACION', 'Actualización sin cambios relevantes.'];
        }

        $entity = $this->logEntityName();

        // Caso especial: activo
        $hasActivo = array_key_exists('activo', $dirty);

        if ($hasActivo) {
            $newActivo = (bool) $dirty['activo'];
            $oldActivo = (bool) $this->getOriginal('activo');

            // Si realmente cambió...
            if ($newActivo !== $oldActivo) {
                $accion = $newActivo ? 'ACTIVACION' : 'ELIMINACION';
                $base = $newActivo
                    ? "Reactivación (activo): {$entity}."
                    : "Baja lógica (activo): {$entity}.";

                // Si además cambiaron otros campos, los incluimos en el texto.
                unset($dirty['activo']);
                if (!empty($dirty)) {
                    $base .= "\nCambios adicionales:\n" . $this->formatDirtyChanges($dirty);
                }

                return [$accion, $base];
            }

            // Si "activo" viene sucio pero no cambió, lo sacamos
            unset($dirty['activo']);
        }

        // Update normal
        $desc = "Actualización: {$entity}.\nCambios:\n" . $this->formatDirtyChanges($dirty);
        return ['ACTUALIZACION', $desc];
    }

    /**
     * Convierte cambios dirty en texto "campo: antes -> después".
     */
    protected function formatDirtyChanges(array $dirty): string {
        $lines = [];

        foreach ($dirty as $field => $newValue) {
            $oldValue = $this->getOriginal($field);

            $old = $this->valueToShortString($oldValue);
            $new = $this->valueToShortString($newValue);

            $lines[] = "- {$field}: {$old} -> {$new}";
        }

        $out = implode("\n", $lines);

        // corta por seguridad
        if (mb_strlen($out) > $this->logMaxLen) {
            $out = mb_substr($out, 0, $this->logMaxLen) . '...';
        }

        return $out;
    }

    /**
     * Humaniza el “nombre” de entidad para logs.
     * - Si existe "nombre" úsalo, si no usa {Tabla}#{id}
     */
    protected function logEntityName(): string {
        $table = $this->getTable();
        $id = $this->getKey();

        $name = null;

        // Intentos comunes
        foreach (['nombre', 'name', 'titulo', 'title', 'codigo'] as $candidate) {
            if (isset($this->{$candidate}) && is_scalar($this->{$candidate}) && trim((string)$this->{$candidate}) !== '') {
                $name = trim((string)$this->{$candidate});
                break;
            }
        }

        return $name ? "{$name} ({$table}#{$id})" : "{$table}#{$id}";
    }

    /**
     * Serializa valores a string corta, sin ensuciar logs.
     */
    protected function valueToShortString($value): string{
        if (is_null($value)) return 'null';
        if (is_bool($value)) return $value ? 'true' : 'false';

        if (is_array($value)) {
            $s = json_encode($value, JSON_UNESCAPED_UNICODE);
            return $this->truncate($s ?: '[array]');
        }

        if (is_object($value)) {
            // evita dumps gigantes
            $s = method_exists($value, '__toString') ? (string) $value : get_class($value);
            return $this->truncate($s);
        }

        return $this->truncate((string) $value);
    }

    // Trunca strings largos.
    protected function truncate(string $value, int $max = 120): string{
        $value = trim($value);
        if (mb_strlen($value) <= $max) return $value;
        return mb_substr($value, 0, $max) . '...';
    }

    /**
     * Inserta el log.
     */
    protected function writeSystemLog(string $accion, string $descripcion): void{
        try {
            SystemLog::create([
                'user_id'    => Auth::id(),
                'accion'     => $accion,
                'tabla'      => $this->getTable(),
                'registro_id'=> (string) $this->getKey(),
                'ip_address' => Request::ip(),
                'user_agent' => substr((string) Request::header('User-Agent'), 0, 500),
                'descripcion'=> mb_substr($descripcion, 0, $this->logMaxLen),
            ]);
        } catch (\Throwable $e) {
            // no reventamos el flujo del sistema por el log
        }
    }

}
