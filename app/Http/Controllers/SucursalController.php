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
    public function index(Request $request)
    {
        $filters = [
            'q'              => (string) $request->query('q', ''),
            'corporativo_id' => $request->query('corporativo_id', ''),
            'activo'         => $request->query('activo', ''),
            'perPage'        => (int) $request->query('perPage', 15),

            // ✅ nuevo: sort por nombre
            'sort'           => $request->query('sort', 'nombre'),
            'dir'            => $request->query('dir', 'asc'), // asc | desc
        ];

        $dir = strtolower($filters['dir']) === 'desc' ? 'desc' : 'asc';
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
            ->when($filters['activo'] !== '' && $filters['activo'] !== null, function ($q) use ($filters) {
                $q->where('activo', (int) $filters['activo']);
            })
            ->orderBy($sort, $dir)
            ->orderBy('id', 'desc');

        $sucursales = $query->paginate($filters['perPage'])->withQueryString();

        return Inertia::render('Sucursales/Index', [
            // ✅ No uses items(); pásale el paginator completo y transforma correctamente
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
            'corporativos' => Corporativo::query()
                ->select('id', 'nombre', 'codigo', 'activo')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(StoreSucursalRequest $request)
    {
        Sucursal::create($request->validated());

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal creada.');
    }

    public function update(UpdateSucursalRequest $request, Sucursal $sucursal)
    {
        // ✅ ya con binding correcto, esto sí pega al registro real
        $sucursal->update($request->validated());

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Sucursal $sucursal)
    {
        $sucursal->delete();

        return redirect()
            ->route('sucursales.index')
            ->with('success', 'Sucursal eliminada.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        // Si tienes policies/roles, aquí validas permisos.

        $ids = $data['ids'];

        // Ojo: si hay FK/restricciones, esto puede fallar. Maneja según tu negocio.
        Sucursal::whereIn('id', $ids)->delete();

        return back()->with('success', 'Sucursales eliminadas correctamente.');
    }
    
}
