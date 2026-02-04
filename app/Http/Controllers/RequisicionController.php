<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requisicion\BulkDestroyRequest;
use App\Http\Requests\Requisicion\RequisicionIndexRequest;
use App\Http\Requests\Requisicion\RequisicionStoreRequest;
use App\Http\Requests\Requisicion\RequisicionUpdateRequest;
use App\Http\Resources\RequisicionResource;
use App\Models\Concepto;
use App\Models\Corporativo;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controlador RequisicionController
 */
class RequisicionController extends Controller {

    // Muestra el listado de requisiciones con filtros, ordenamiento y paginación.
    public function index(RequisicionIndexRequest $request): Response {
        $user = $request->user();
        $rol  = $user->rol;
        $q = $request->validated();
        // Número de elementos por página aceptados
        $perPage = (int)($q['perPage'] ?? 15);
        if (!in_array($perPage, [10,15,25,50,100], true)) {
            $perPage = 15;
        }
        // Ordenamiento
        $sort = $q['sort'] ?? 'id';
        $dir  = $q['dir'] ?? 'desc';
        $allowedSort = ['id','folio','tipo','status','monto_total','fecha_solicitud','fecha_autorizacion'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'id';
        if (!in_array($dir, ['asc','desc'], true)) $dir = 'desc';
        // Construir consulta base con relaciones y filtros
        $query = Requisicion::query()
            ->with([
                'comprador:id,nombre',
                'sucursal:id,nombre,codigo,corporativo_id,activo',
                'solicitante:id,nombre,apellido_paterno,apellido_materno,sucursal_id,puesto,activo',
                'concepto:id,nombre,activo',
                'proveedor:id,razon_social',
            ])
            ->search($q['q'] ?? null)
            ->statusTab($q['tab'] ?? 'TODAS')
            ->when(($q['status'] ?? '') !== '', fn($qq) => $qq->where('status', $q['status']))
            ->when(($q['tipo']   ?? '') !== '', fn($qq) => $qq->where('tipo', $q['tipo']))
            ->when(($q['comprador_corp_id'] ?? '') !== '', fn($qq) => $qq->where('comprador_corp_id', (int)$q['comprador_corp_id']))
            ->when(($q['sucursal_id'] ?? '') !== '', fn($qq) => $qq->where('sucursal_id', (int)$q['sucursal_id']))
            ->when(($q['solicitante_id'] ?? '') !== '', fn($qq) => $qq->where('solicitante_id', (int)$q['solicitante_id']))
            ->dateRangeSolicitud($q['fecha_from'] ?? null, $q['fecha_to'] ?? null);
        // Restricción por rol: el colaborador sólo ve sus requisiciones
        if ($rol === 'COLABORADOR') {
            $empleadoId = $user->empleado_id;
            if ($empleadoId) {
                $query->where('solicitante_id', $empleadoId);
            } else {
                $query->whereRaw('1=0'); // si no tiene empleado, no ve nada
            }
        }
        $requisiciones = $query
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();
        // Catálogos: corporativos, sucursales, conceptos, proveedores, empleados
        $corporativos = Corporativo::select('id','nombre','activo')->orderBy('nombre')->get();
        $sucursales   = Sucursal::select('id','nombre','codigo','corporativo_id','activo')->orderBy('nombre')->get();
        $conceptos    = Concepto::select('id','nombre','activo')->orderBy('nombre')->get();
        $proveedores  = Proveedor::select('id','razon_social')->orderBy('razon_social')->limit(500)->get();
        $empleadosQ   = Empleado::select('id','nombre','apellido_paterno','apellido_materno','sucursal_id','area_id','puesto','activo')
            ->with(['area:id,nombre'])
            ->orderBy('nombre');
        if ($rol === 'COLABORADOR' && $user->empleado_id) {
            $empleadosQ->where('id', $user->empleado_id);
        }
        $empleados = $empleadosQ->get()->map(fn($e) => [
            'id'         => $e->id,
            'nombre'     => trim($e->nombre.' '.$e->apellido_paterno.' '.($e->apellido_materno ?? '')),
            'sucursal_id'=> $e->sucursal_id,
            'puesto'     => $e->puesto,
            'area_id'    => $e->area_id,
            'area'       => $e->area ? ['id' => $e->area->id,'nombre' => $e->area->nombre] : null,
            'activo'     => $e->activo,
        ]);
        // Conteos por pestaña (sin aplicar tab para no sesgar el conteo)
        $baseCounts = Requisicion::query()
            ->search($q['q'] ?? null)
            ->when(($q['status'] ?? '') !== '', fn($qq) => $qq->where('status', $q['status']))
            ->when(($q['tipo']   ?? '') !== '', fn($qq) => $qq->where('tipo', $q['tipo']))
            ->when(($q['comprador_corp_id'] ?? '') !== '', fn($qq) => $qq->where('comprador_corp_id', (int)$q['comprador_corp_id']))
            ->when(($q['sucursal_id'] ?? '') !== '', fn($qq) => $qq->where('sucursal_id', (int)$q['sucursal_id']))
            ->when(($q['solicitante_id'] ?? '') !== '', fn($qq) => $qq->where('solicitante_id', (int)$q['solicitante_id']))
            ->dateRangeSolicitud($q['fecha_from'] ?? null, $q['fecha_to'] ?? null);
        if ($rol === 'COLABORADOR') {
            $empleadoId = $user->empleado_id;
            if ($empleadoId) {
                $baseCounts->where('solicitante_id', $empleadoId);
            } else {
                $baseCounts->whereRaw('1=0');
            }
        }
        $counts = [
            'pendientes'  => (clone $baseCounts)->statusTab('PENDIENTES')->count(),
            'autorizadas' => (clone $baseCounts)->statusTab('AUTORIZADAS')->count(),
            'rechazadas'  => (clone $baseCounts)->statusTab('RECHAZADAS')->count(),
            'todas'       => (clone $baseCounts)->count(),
        ];
        return Inertia::render('Requisiciones/Index', [
            'filters' => [
                'q'              => $q['q'] ?? '',
                'tab'            => $q['tab'] ?? 'TODAS',
                'status'         => $q['status'] ?? '',
                'tipo'           => $q['tipo'] ?? '',
                'comprador_corp_id'=> $q['comprador_corp_id'] ?? '',
                'sucursal_id'    => $q['sucursal_id'] ?? '',
                'solicitante_id' => $q['solicitante_id'] ?? '',
                'fecha_from'     => $q['fecha_from'] ?? '',
                'fecha_to'       => $q['fecha_to'] ?? '',
                'perPage'        => $perPage,
                'sort'           => $sort,
                'dir'            => $dir,
            ],
            'counts' => $counts,
            'requisiciones' => RequisicionResource::collection($requisiciones),
            'catalogos' => [
                'corporativos' => $corporativos,
                'sucursales'   => $sucursales,
                'empleados'    => $empleados,
                'conceptos'    => $conceptos,
                'proveedores'  => $proveedores->map(fn($p) => ['id' => $p->id, 'nombre' => $p->razon_social]),
            ],
            'ui' => [
                'canReject' => in_array($rol, ['ADMIN','CONTADOR'], true),
                'canCreate' => true,
                'rol'       => $rol,
            ],
        ]);
    }

    /**
     * Muestra la vista de creación de requisiciones.
     * Carga catálogos iniciales. Un colaborador sólo puede elegir su propio empleado.
     */
    public function create(Request $request): Response {
        $user = $request->user();
        $rol  = $user->rol;
        $corporativos = Corporativo::select('id','nombre','activo')->orderBy('nombre')->get();
        $sucursales   = Sucursal::select('id','nombre','codigo','corporativo_id','activo')->orderBy('nombre')->get();
        $conceptos    = Concepto::select('id','nombre','activo')->orderBy('nombre')->get();
        $proveedores  = Proveedor::select('id','razon_social')->orderBy('razon_social')->limit(500)->get()
            ->map(fn($p) => ['id' => $p->id,'nombre' => $p->razon_social]);
        $empleadosQ = Empleado::select('id','nombre','apellido_paterno','apellido_materno','sucursal_id','area_id','puesto','activo')
            ->with(['area:id,nombre'])
            ->orderBy('nombre');
        if ($rol === 'COLABORADOR' && $user->empleado_id) {
            $empleadosQ->where('id', $user->empleado_id);
        }
        $empleados = $empleadosQ->get()->map(fn($e) => [
            'id'         => $e->id,
            'nombre'     => trim($e->nombre.' '.$e->apellido_paterno.' '.($e->apellido_materno ?? '')),
            'sucursal_id'=> $e->sucursal_id,
            'puesto'     => $e->puesto,
            'area_id'    => $e->area_id,
            'area'       => $e->area ? ['id' => $e->area->id,'nombre' => $e->area->nombre] : null,
            'activo'     => $e->activo,
        ]);
        return Inertia::render('Requisiciones/Create', [
            'routes' => [
                'index'           => route('requisiciones.index'),
                'store'           => route('requisiciones.store'),
                'proveedoresStore'=> route('proveedores.store'),
            ],
            'catalogos' => [
                'corporativos' => $corporativos,
                'sucursales'   => $sucursales,
                'empleados'    => $empleados,
                'conceptos'    => $conceptos,
                'proveedores'  => $proveedores,
            ],
            'ui' => [
                'rol' => $rol,
            ],
        ]);
    }

    /**
     * Guarda una nueva requisición.
     * Se calcula el comprador_corp_id a partir de la sucursal.
     * Un colaborador siempre será el solicitante.
     */
    public function store(RequisicionStoreRequest $request): RedirectResponse {
        $user = $request->user();
        $rol  = $user->rol;

        $data = $request->validated();
        // Si el rol es colaborador, forzamos el solicitante_id a su empleado.
        if ($rol === 'COLABORADOR') {
            if (!$user->empleado_id) {
                return back()->withErrors(['solicitante_id' => 'Tu usuario no tiene empleado ligado.']);
            }
            $data['solicitante_id'] = $user->empleado_id;
        }
        // Verificamos sucursal válida
        $sucursalId = (int)($data['sucursal_id'] ?? 0);
        $sucursal   = Sucursal::select('id','corporativo_id','nombre')->find($sucursalId);
        if (!$sucursal) {
            return back()->withErrors(['sucursal_id' => 'Sucursal inválida.']);
        }
        // Derivamos el corporativo comprador de la sucursal
        $data['comprador_corp_id'] = (int)$sucursal->corporativo_id;
        // Lugar de entrega = sucursal
        $data['lugar_entrega_texto'] = $sucursal->nombre;
        // Auditoría
        $data['creada_por_user_id'] = $user->id;
        // Procesamos detalles y retiramos del arreglo principal
        $detalles = $data['detalles'];
        unset($data['detalles']);
        // Creamos la requisición
        $requisicion = Requisicion::create($data);
        // Persistimos los detalles asociados a la requisición
        if (method_exists($requisicion, 'detalles')) {
            $requisicion->detalles()->createMany($detalles);
        }
        return redirect()->route('requisiciones.index')
            ->with('success', 'Requisición creada correctamente.');
    }

    // Actualiza una requisición.
    public function update(RequisicionUpdateRequest $request, Requisicion $requisicion): RedirectResponse {
        $data = $request->validated();
        $detalles = $data['detalles'] ?? null;
        unset($data['detalles']);
        $requisicion->update($data);
        // Actualizar detalles si fueron enviados
        if (is_array($detalles)) {
            // Estrategia simple: borrar los existentes y crear los nuevos
            $requisicion->detalles()->delete();
            $requisicion->detalles()->createMany($detalles);
        }
        return redirect()->route('requisiciones.index')
            ->with('success', 'Requisición actualizada.');
    }

    /**
     * Eliminar (marcar como eliminada) una requisición.
     * - Un colaborador sólo puede eliminar si la requisición está en BORRADOR.
     * - Admin/Contador pueden eliminar cualquier requisición.
     */
    public function destroy(Request $request, Requisicion $requisicion): RedirectResponse {
        $rol = $request->user()->rol;
        // Si es colaborador, verificar que la requisición le pertenece y esté en borrador
        if ($rol === 'COLABORADOR') {
            $empleadoId = $request->user()->empleado_id;
            abort_unless($empleadoId && $requisicion->solicitante_id == $empleadoId, 403);
            abort_unless($requisicion->status === 'BORRADOR', 403);
        } elseif (!in_array($rol, ['ADMIN','CONTADOR'], true)) {
            abort(403);
        }
        // Marcamos como eliminada
        $requisicion->update(['status' => 'ELIMINADA']);
        return redirect()->route('requisiciones.index')
            ->with('success', 'Requisición eliminada.');
    }

    /**
     * Eliminación masiva: marca varias requisiciones como eliminadas.
     */
    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse {
        $rol = $request->user()->rol;
        abort_unless(in_array($rol, ['ADMIN','CONTADOR'], true), 403);
        $ids = $request->validated()['ids'];
        Requisicion::query()
            ->whereIn('id', $ids)
            ->update(['status' => 'ELIMINADA']);
        return redirect()->route('requisiciones.index')
            ->with('success', 'Requisiciones eliminadas.');
    }

}
 