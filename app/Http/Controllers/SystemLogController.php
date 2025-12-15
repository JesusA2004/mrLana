<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemLogController extends Controller
{
    /**
     * System Logs
     * - Filtros en tiempo real (querystring)
     * - Paginación estable
     * - Opciones para selects (tablas/acciones/usuarios)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        /**
         * Normalización de filtros:
         * - Si llega "" => lo tratamos como null
         * - perPage: clamp 10..100
         * - user_id: int|null
         */
        $filters = [
            'from'   => trim((string) $request->input('from', '')),
            'to'     => trim((string) $request->input('to', '')),
            'tabla'  => trim((string) $request->input('tabla', '')),
            'accion' => trim((string) $request->input('accion', '')),
            'ip'     => trim((string) $request->input('ip', '')),
            'q'      => trim((string) $request->input('q', '')),
        ];

        // user_id puede venir como "" en selects => null
        $userIdRaw = $request->input('user_id', null);
        $filters['user_id'] = is_numeric($userIdRaw) ? (int) $userIdRaw : null;

        $perPage = (int) $request->input('perPage', 15);
        $filters['perPage'] = max(10, min(100, $perPage ?: 15));

        $query = SystemLog::query()
            ->with(['user:id,name,email,rol'])
            ->latest('id');

        if ($filters['from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if ($filters['tabla'] !== '') {
            $query->where('tabla', $filters['tabla']);
        }
        if ($filters['accion'] !== '') {
            $query->where('accion', $filters['accion']);
        }
        if (!is_null($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if ($filters['ip'] !== '') {
            $query->where('ip_address', 'like', '%' . $filters['ip'] . '%');
        }
        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('descripcion', 'like', "%{$q}%")
                    ->orWhere('tabla', 'like', "%{$q}%")
                    ->orWhere('accion', 'like', "%{$q}%")
                    // registro_id es BIGINT: LIKE funciona en MySQL/MariaDB, pero lo protegemos casteando a string
                    ->orWhereRaw("CAST(registro_id AS CHAR) LIKE ?", ["%{$q}%"])
                    ->orWhere('ip_address', 'like', "%{$q}%");
            });
        }

        /**
         * Nota: withQueryString() es clave para que al paginar no se pierdan filtros.
         */
        $logs = $query->paginate($filters['perPage'])->withQueryString();

        // Opciones para filtros
        $tablas = SystemLog::query()
            ->select('tabla')
            ->whereNotNull('tabla')
            ->where('tabla', '<>', '')
            ->distinct()
            ->orderBy('tabla')
            ->pluck('tabla')
            ->values();

        $acciones = SystemLog::query()
            ->select('accion')
            ->whereNotNull('accion')
            ->where('accion', '<>', '')
            ->distinct()
            ->orderBy('accion')
            ->pluck('accion')
            ->values();

        $usuarios = User::query()
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('SystemLogs/Index', [
            'logs'     => $logs,
            'filters'  => $filters,
            'tablas'   => $tablas,
            'acciones' => $acciones,
            'usuarios' => $usuarios,
        ]);
    }
}
