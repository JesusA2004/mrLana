<?php

namespace App\Http\Controllers;

use App\Http\Requests\Plantilla\PlantillaStoreRequest;
use App\Http\Requests\Plantilla\PlantillaUpdateRequest;
use App\Http\Resources\PlantillaResource;
use App\Models\Concepto;
use App\Models\Corporativo;
use App\Models\Empleado;
use App\Models\Plantilla;
use App\Models\Proveedor;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlantillaController extends Controller
{
    /**
     * Listado de plantillas.
     * COLABORADOR: sólo las suyas. ADMIN/CONTADOR: todas.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $rol  = strtoupper((string) ($user->rol ?? 'COLABORADOR'));

        // Filtros
        $filters = [
            'q'       => trim((string) $request->input('q', '')),
            'status'  => trim((string) $request->input('status', '')),
            'perPage' => (int) $request->input('perPage', 20),
            'sort'    => trim((string) $request->input('sort', 'nombre')),
            'dir'     => strtolower((string) $request->input('dir', 'asc')) === 'desc' ? 'desc' : 'asc',
        ];

        // Sanitiza perPage
        if ($filters['perPage'] <= 0) $filters['perPage'] = 20;
        if ($filters['perPage'] > 100) $filters['perPage'] = 100;

        // Sanitiza sort
        $allowedSort = ['nombre', 'status', 'monto_total', 'created_at', 'updated_at'];
        if (!in_array($filters['sort'], $allowedSort, true)) {
            $filters['sort'] = 'nombre';
        }

        $query = Plantilla::query()
            // ✅ IMPORTANTE: cargar relaciones para el Index.vue
            ->with([
                'sucursal:id,nombre',
                'solicitante:id,nombre,apellido_paterno,apellido_materno',
                'proveedor:id,razon_social',
                'concepto:id,nombre',
            ])
            ->when($rol === 'COLABORADOR', fn($qq) => $qq->where('user_id', $user->id))
            ->when($filters['q'] !== '', function ($qq) use ($filters) {
                $q = $filters['q'];
                $qq->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('observaciones', 'like', "%{$q}%");
                });
            })
            ->when($filters['status'] !== '', fn($qq) => $qq->where('status', $filters['status']))
            ->orderBy($filters['sort'], $filters['dir']);

        $plantillas = $query
            ->paginate($filters['perPage'])
            ->withQueryString();

        return Inertia::render('Plantillas/Index', [
            'plantillas' => PlantillaResource::collection($plantillas),
            'filters'    => $filters,
        ]);
    }

    /**
     * Formulario create.
     */
    public function create(Request $request): Response
    {
        $corporativos = Corporativo::select('id', 'nombre', 'activo')->orderBy('nombre')->get();
        $sucursales   = Sucursal::select('id', 'nombre', 'codigo', 'corporativo_id', 'activo')->orderBy('nombre')->get();
        $conceptos    = Concepto::select('id', 'nombre', 'activo')->orderBy('nombre')->get();

        $proveedores = Proveedor::select('id', 'razon_social')
            ->orderBy('razon_social')
            ->limit(500)
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'nombre' => $p->razon_social]);

        $empleados = Empleado::select('id', 'nombre', 'apellido_paterno', 'apellido_materno', 'sucursal_id', 'puesto', 'activo')
            ->orderBy('nombre')
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'nombre'      => trim($e->nombre . ' ' . $e->apellido_paterno . ' ' . ($e->apellido_materno ?? '')),
                'sucursal_id' => $e->sucursal_id,
                'activo'      => $e->activo,
            ]);

        return Inertia::render('Plantillas/Create', [
            'catalogos' => [
                'corporativos' => $corporativos,
                'sucursales'   => $sucursales,
                'empleados'    => $empleados,
                'conceptos'    => $conceptos,
                'proveedores'  => $proveedores,
            ],
        ]);
    }

    /**
     * Guardar plantilla.
     */
    public function store(PlantillaStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $detalles = $data['detalles'] ?? [];
        unset($data['detalles']);

        // Si viene sucursal_id y no comprador_corp_id, deducirlo
        if (!empty($data['sucursal_id']) && empty($data['comprador_corp_id'])) {
            $sucursal = Sucursal::select('id', 'corporativo_id')->find($data['sucursal_id']);
            $data['comprador_corp_id'] = $sucursal?->corporativo_id;
        }

        $data['user_id'] = $user->id;
        $data['status']  = 'BORRADOR';

        $plantilla = Plantilla::create($data);

        if (!empty($detalles)) {
            $plantilla->detalles()->createMany($detalles);
        }

        return redirect()->route('plantillas.index')
            ->with('success', 'Plantilla guardada.');
    }

    /**
     * Edit form.
     */
    public function edit(Request $request, Plantilla $plantilla): Response
    {
        $user = $request->user();
        $rol  = strtoupper((string) ($user->rol ?? 'COLABORADOR'));

        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        $corporativos = Corporativo::select('id', 'nombre', 'activo')->orderBy('nombre')->get();
        $sucursales   = Sucursal::select('id', 'nombre', 'codigo', 'corporativo_id', 'activo')->orderBy('nombre')->get();
        $conceptos    = Concepto::select('id', 'nombre', 'activo')->orderBy('nombre')->get();

        $proveedores = Proveedor::select('id', 'razon_social')
            ->orderBy('razon_social')
            ->limit(500)
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'nombre' => $p->razon_social]);

        $empleados = Empleado::select('id', 'nombre', 'apellido_paterno', 'apellido_materno', 'sucursal_id', 'puesto', 'activo')
            ->orderBy('nombre')
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'nombre'      => trim($e->nombre . ' ' . $e->apellido_paterno . ' ' . ($e->apellido_materno ?? '')),
                'sucursal_id' => $e->sucursal_id,
                'puesto'      => $e->puesto,
                'activo'      => $e->activo,
            ]);

        $plantilla->load(['detalles', 'sucursal', 'solicitante', 'proveedor', 'concepto']);

        return Inertia::render('Plantillas/Edit', [
            'plantilla' => new PlantillaResource($plantilla),
            'catalogos' => [
                'corporativos' => $corporativos,
                'sucursales'   => $sucursales,
                'empleados'    => $empleados,
                'conceptos'    => $conceptos,
                'proveedores'  => $proveedores,
            ],
            'routes' => [
                'index'  => route('plantillas.index'),
                'update' => route('plantillas.update', $plantilla),
            ],
            'ui' => [
                'rol' => $rol,
            ],
        ]);
    }

    /**
     * Update.
     */
    public function update(PlantillaUpdateRequest $request, Plantilla $plantilla): RedirectResponse
    {
        $user = $request->user();
        $rol  = strtoupper((string) ($user->rol ?? 'COLABORADOR'));

        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validated();
        $detalles = $data['detalles'] ?? [];
        unset($data['detalles']);

        if (!empty($data['sucursal_id']) && empty($data['comprador_corp_id'])) {
            $sucursal = Sucursal::select('id', 'corporativo_id')->find($data['sucursal_id']);
            $data['comprador_corp_id'] = $sucursal?->corporativo_id;
        }

        $plantilla->update($data);

        $plantilla->detalles()->delete();
        if (!empty($detalles)) {
            $plantilla->detalles()->createMany($detalles);
        }

        return redirect()->route('plantillas.index')
            ->with('success', 'Plantilla actualizada.');
    }

    /**
     * Soft delete (status).
     */
    public function destroy(Request $request, Plantilla $plantilla): RedirectResponse
    {
        $user = $request->user();
        $rol  = strtoupper((string) ($user->rol ?? 'COLABORADOR'));

        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        $plantilla->update(['status' => 'ELIMINADA']);

        return redirect()->route('plantillas.index')
            ->with('success', 'Plantilla eliminada.');
    }

    /**
     * JSON show para precargar (uso futuro en requisiciones).
     */
    public function show(Request $request, Plantilla $plantilla)
    {
        $user = $request->user();
        $rol  = strtoupper((string) ($user->rol ?? 'COLABORADOR'));

        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        $plantilla->load(['detalles', 'sucursal', 'solicitante', 'proveedor', 'concepto']);

        return new PlantillaResource($plantilla);
    }
}
