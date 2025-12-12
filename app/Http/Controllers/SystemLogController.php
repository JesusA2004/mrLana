<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Solo ADMIN
        abort_unless($user && $user->rol === 'ADMIN', 403);

        $filters = [
            'from'    => $request->string('from')->toString(),   // YYYY-MM-DD
            'to'      => $request->string('to')->toString(),     // YYYY-MM-DD
            'tabla'   => $request->string('tabla')->toString(),
            'accion'  => $request->string('accion')->toString(),
            'user_id' => $request->integer('user_id') ?: null,
            'ip'      => $request->string('ip')->toString(),
            'q'       => $request->string('q')->toString(),
            'perPage' => max(10, min(100, (int) $request->input('perPage', 15))),
        ];

        $query = SystemLog::query()
            ->with(['user:id,name,email,rol'])
            ->latest('id');

        if ($filters['from']) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if ($filters['to']) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if ($filters['tabla']) {
            $query->where('tabla', $filters['tabla']);
        }
        if ($filters['accion']) {
            $query->where('accion', $filters['accion']);
        }
        if ($filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }
        if ($filters['ip']) {
            $query->where('ip_address', 'like', '%' . $filters['ip'] . '%');
        }
        if ($filters['q']) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('descripcion', 'like', "%{$q}%")
                    ->orWhere('tabla', 'like', "%{$q}%")
                    ->orWhere('accion', 'like', "%{$q}%")
                    ->orWhere('registro_id', 'like', "%{$q}%")
                    ->orWhere('ip_address', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate($filters['perPage'])->withQueryString();

        // Opciones para filtros (rápido y práctico)
        $tablas = SystemLog::query()
            ->select('tabla')
            ->whereNotNull('tabla')
            ->where('tabla', '<>', '')
            ->distinct()
            ->orderBy('tabla')
            ->pluck('tabla');

        $acciones = SystemLog::query()
            ->select('accion')
            ->whereNotNull('accion')
            ->where('accion', '<>', '')
            ->distinct()
            ->orderBy('accion')
            ->pluck('accion');

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
