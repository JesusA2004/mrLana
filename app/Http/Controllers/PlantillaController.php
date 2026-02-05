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

/**
 * Controlador PlantillaController
 *
 * Permite gestionar las plantillas de requisiciones:
 * - Listar las plantillas de un usuario (o todas para admin/contador).
 * - Crear una nueva plantilla con detalles.
 * - Actualizar/editar la plantilla.
 * - Eliminar (marcar) una plantilla.
 * - Cargar una plantilla (para precargar campos en la creación de requisiciones).
 */
class PlantillaController extends Controller {

    /**
     * Muestra la lista de plantillas.
     * Un colaborador sólo ve sus plantillas; admin/contador pueden ver todas.
     */
    public function index(Request $request)
    {
        $filters = [
            'q'      => $request->input('q', ''),
            'status' => $request->input('status', ''),
            'perPage'=> (int) $request->input('perPage', 20),
            'sort'   => $request->input('sort', 'nombre'),
            'dir'    => $request->input('dir', 'asc'),
        ];

        $user = $request->user();
        $rol  = $user->rol;

        $query = Plantilla::query()
            ->when($rol === 'COLABORADOR', fn($qq) => $qq->where('user_id', $user->id))
            ->when($filters['q'] !== '', function ($qq) use ($filters) {
                $q = trim($filters['q']);
                $qq->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('observaciones', 'like', "%{$q}%");
                });
            })
            ->when($filters['status'] !== '', fn ($qq) => $qq->where('status', $filters['status']))
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
     * Formulario para crear una nueva plantilla.
     */
    public function create(Request $request): Response {
        // Cargamos catálogos básicos
        $corporativos = Corporativo::select('id','nombre','activo')->orderBy('nombre')->get();
        $sucursales   = Sucursal::select('id','nombre','codigo','corporativo_id','activo')->orderBy('nombre')->get();
        $conceptos    = Concepto::select('id','nombre','activo')->orderBy('nombre')->get();
        $proveedores  = Proveedor::select('id','razon_social')->orderBy('razon_social')->limit(500)->get()
            ->map(fn($p) => ['id' => $p->id,'nombre' => $p->razon_social]);
        $empleados    = Empleado::select('id','nombre','apellido_paterno','apellido_materno','sucursal_id','puesto','activo')
            ->orderBy('nombre')
            ->get()
            ->map(fn($e) => [
                'id'       => $e->id,
                'nombre'   => trim($e->nombre.' '.$e->apellido_paterno.' '.($e->apellido_materno ?? '')),
                'sucursal_id' => $e->sucursal_id,
                'activo'   => $e->activo,
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
     * Guarda una nueva plantilla.
     * El user_id se toma del usuario autenticado; el status se define como BORRADOR.
     */
    public function store(PlantillaStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $detalles = $data['detalles'];
        unset($data['detalles']);

        // Ajustamos el comprador_corp_id a partir de la sucursal seleccionada (si existe)
        if (!empty($data['sucursal_id']) && empty($data['comprador_corp_id'])) {
            $sucursal = Sucursal::select('id', 'corporativo_id')->find($data['sucursal_id']);
            $data['comprador_corp_id'] = $sucursal?->corporativo_id;
        }

        $data['user_id'] = $user->id;
        $data['status']  = 'BORRADOR';

        $plantilla = Plantilla::create($data);

        // Guardar detalles
        $plantilla->detalles()->createMany($detalles);

        return redirect()->route('plantillas.index')
            ->with('success', 'Plantilla guardada.');
    }

    /**
     * Formulario de edición de una plantilla existente.
     */
    public function edit(Request $request, Plantilla $plantilla): Response
    {
        $user = $request->user();
        $rol  = $user->rol;

        // Autorización: colaborador sólo puede editar sus plantillas
        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        // Catálogos
        $corporativos = Corporativo::select('id', 'nombre', 'activo')->orderBy('nombre')->get();
        $sucursales   = Sucursal::select('id', 'nombre', 'codigo', 'corporativo_id', 'activo')->orderBy('nombre')->get();
        $conceptos    = Concepto::select('id', 'nombre', 'activo')->orderBy('nombre')->get();
        $proveedores  = Proveedor::select('id', 'razon_social')->orderBy('razon_social')->limit(500)->get()
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->razon_social]);
        $empleados    = Empleado::select('id', 'nombre', 'apellido_paterno', 'apellido_materno', 'sucursal_id', 'puesto', 'activo')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($e) => [
                'id'           => $e->id,
                'nombre'       => trim($e->nombre . ' ' . $e->apellido_paterno . ' ' . ($e->apellido_materno ?? '')),
                'sucursal_id'  => $e->sucursal_id,
                'puesto'       => $e->puesto,
                'activo'       => $e->activo,
            ]);

        return Inertia::render('Plantillas/Edit', [
            'plantilla' => new PlantillaResource($plantilla->load(['detalles', 'sucursal', 'solicitante', 'proveedor', 'concepto'])),
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
     * Actualiza la plantilla y sus detalles.
     */
    public function update(PlantillaUpdateRequest $request, Plantilla $plantilla): RedirectResponse
    {
        $user = $request->user();
        $rol  = $user->rol;

        // Autorización: colaborador sólo actualiza sus plantillas
        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        $data = $request->validated();
        $detalles = $data['detalles'];
        unset($data['detalles']);

        // Ajustar comprador_corp_id si viene de sucursal
        if (!empty($data['sucursal_id']) && empty($data['comprador_corp_id'])) {
            $sucursal = Sucursal::select('id', 'corporativo_id')->find($data['sucursal_id']);
            $data['comprador_corp_id'] = $sucursal?->corporativo_id;
        }

        $plantilla->update($data);

        // Actualizar detalles: estrategia simple (borrar y recrear)
        $plantilla->detalles()->delete();
        $plantilla->detalles()->createMany($detalles);

        return redirect()->route('plantillas.index')
            ->with('success', 'Plantilla actualizada.');
    }

    /**
     * Elimina (marca) una plantilla.
     */
    public function destroy(Request $request, Plantilla $plantilla): RedirectResponse
    {
        $user = $request->user();
        $rol  = $user->rol;

        // Autorización: colaborador sólo elimina sus plantillas
        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        $plantilla->update(['status' => 'ELIMINADA']);

        return redirect()->route('plantillas.index')
            ->with('success', 'Plantilla eliminada.');
    }

    /**
     * Devuelve una plantilla en formato JSON para precargar en la creación de requisiciones.
     * Puede usarse desde la UI para seleccionar una plantilla y cargar sus campos.
     */
    public function show(Plantilla $plantilla)
    {
        $user = request()->user();
        $rol  = $user->rol;

        if ($rol === 'COLABORADOR' && $plantilla->user_id !== $user->id) {
            abort(403);
        }

        return new PlantillaResource($plantilla->load(['detalles', 'sucursal', 'solicitante', 'proveedor', 'concepto']));
    }
}
