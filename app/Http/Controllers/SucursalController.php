<?php

namespace App\Http\Controllers;

use App\Models\Sucursal;
use App\Models\Corporativo;
use App\Http\Resources\SucursalResource;
use App\Http\Requests\Sucursal\StoreSucursalRequest;
use App\Http\Requests\Sucursal\UpdateSucursalRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SucursalController extends Controller
{
    /**
     * Index: lista sucursales con filtros + paginación.
     * Reglas:
     * - Por defecto muestra SOLO activas (activo=1).
     * - El combo de corporativos SOLO trae corporativos activos.
     */
    public function index(Request $request)
    {
        $filters = [
            'q'              => (string) $request->query('q', ''),
            'corporativo_id' => $request->query('corporativo_id', ''),
            // default: activos
            'activo'         => (string) $request->query('activo', '1'),
            'perPage'        => (int) $request->query('perPage', 15),

            'sort'           => (string) $request->query('sort', 'nombre'),
            'dir'            => (string) $request->query('dir', 'asc'),
        ];

        $dir  = strtolower($filters['dir']) === 'desc' ? 'desc' : 'asc';
        $sort = in_array($filters['sort'], ['nombre', 'id'], true) ? $filters['sort'] : 'nombre';

        $query = Sucursal::query()
            ->with(['corporativo:id,nombre,codigo'])
            ->when($filters['q'], function ($q) use ($filters) {
                $v = trim($filters['q']);
                $q->where(function ($qq) use ($v) {
                    $qq->where('nombre', 'like', "%{$v}%")
                        ->orWhere('codigo', 'like', "%{$v}%")
                        ->orWhere('ciudad', 'like', "%{$v}%")
                        ->orWhere('estado', 'like', "%{$v}%")
                        ->orWhere('direccion', 'like', "%{$v}%");
                });
            })
            ->when($filters['corporativo_id'] !== '' && $filters['corporativo_id'] !== null, function ($q) use ($filters) {
                $q->where('corporativo_id', (int) $filters['corporativo_id']);
            })
            // aplica filtro activo (por default '1')
            ->when($filters['activo'] !== '' && $filters['activo'] !== null, function ($q) use ($filters) {
                $q->where('activo', (int) $filters['activo']);
            })
            ->orderBy($sort, $dir)
            ->orderBy('id', 'desc');

        $sucursales = $query->paginate($filters['perPage'])->withQueryString();

        return Inertia::render('Sucursales/Index', [
            'sucursales' => [
                'data'         => SucursalResource::collection($sucursales)->resolve(),
                'links'        => $sucursales->linkCollection(),
                'current_page' => $sucursales->currentPage(),
                'last_page'    => $sucursales->lastPage(),
                'total'        => $sucursales->total(),
                'per_page'     => $sucursales->perPage(),
                'from'         => $sucursales->firstItem(),
                'to'           => $sucursales->lastItem(),
            ],
            'filters' => $filters,

            // SOLO corporativos activos para el filtro/selector
            'corporativos' => Corporativo::query()
                ->select('id', 'nombre', 'codigo')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    /**
     * Store: crea sucursal.
     * Recomendación de negocio: crear en activo=true por default, si tu request lo trae.
     */
    public function store(StoreSucursalRequest $request)
    {
        $data = $request->validated();

        // Si tu negocio quiere que siempre nazcan activas:
        // $data['activo'] = true;

        Sucursal::create($data);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal creada.');
    }

    /**
     * Update: SOLO actualiza campos editables.
     * Regla: NO se permite cambiar "activo" desde aquí.
     */
    public function update(UpdateSucursalRequest $request, Sucursal $sucursal)
    {
        $data = $request->validated();

        // blindaje: el estado NO se toca aquí
        unset($data['activo']);

        $sucursal->update($data);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal actualizada.');
    }

    /**
     * Destroy: baja lógica (activo=false).
     * Regla: si ya está inactiva, no hace nada.
     */
    public function destroy(Sucursal $sucursal)
    {
        if (!$sucursal->activo) {
            return redirect()
                ->route('sucursales.index')
                ->with('success', 'La sucursal ya se encontraba dada de baja.');
        }

        $sucursal->update(['activo' => false]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal dada de baja correctamente.');
    }

    /**
     * Activate: reactivación dedicada (botón Activar).
     * Regla: si ya está activa, no hace nada.
     */
    public function activate(Sucursal $sucursal)
    {
        if ($sucursal->activo) {
            return redirect()
                ->route('sucursales.index')
                ->with('success', 'La sucursal ya estaba activa.');
        }

        $sucursal->update(['activo' => true]);

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal reactivada correctamente.');
    }

    /**
     * bulkDestroy: baja lógica masiva (NO delete físico).
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $ids = $data['ids'];

        // Baja lógica masiva
        Sucursal::whereIn('id', $ids)->where('activo', true)->update(['activo' => false]);

        return back()->with('success', 'Sucursales dadas de baja correctamente.');
    }
}
