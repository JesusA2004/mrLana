<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequisicionRequest;
use App\Http\Requests\UpdateRequisicionRequest;
use App\Http\Resources\RequisicionResource;
use App\Models\Concepto;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Sucursal;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * RequisicionController (CRUD único)
 *
 * Importante:
 * - Logs se registran automático en el Model (trait).
 */
class RequisicionController extends Controller
{

    // Metodo para listar las requisiciones con filtros y paginación
    public function index(Request $request)
    {
        $filters = [
            'q'              => $request->string('q')->toString(),
            'empleado_id'    => $request->integer('empleado_id'),
            'proveedor_id'   => $request->integer('proveedor_id'),
            'sucursal_id'    => $request->integer('sucursal_id'),
            'status'         => $request->string('status')->toString(),
            'fecha_inicio'   => $request->date('fecha_inicio'),
            'fecha_fin'      => $request->date('fecha_fin'),
        ];

        $requisiciones = Requisicion::query()
            ->with([
                'sucursal:id,nombre',
                'solicitante:id,nombre,apellido_paterno',
                'proveedor:id,nombre',
            ])
            ->when($filters['q'], fn ($q) =>
                $q->where(function ($sub) use ($filters) {
                    $sub->where('folio_unico', 'like', "%{$filters['q']}%")
                        ->orWhere('descripcion', 'like', "%{$filters['q']}%");
                })
            )
            ->when($filters['empleado_id'], fn ($q) =>
                $q->where('solicitante_id', $filters['empleado_id'])
            )
            ->when($filters['proveedor_id'], fn ($q) =>
                $q->where('proveedor_id', $filters['proveedor_id'])
            )
            ->when($filters['sucursal_id'], fn ($q) =>
                $q->where('sucursal_id', $filters['sucursal_id'])
            )
            ->when($filters['status'], fn ($q) =>
                $q->where('status', $filters['status'])
            )
            ->when($filters['fecha_inicio'], fn ($q) =>
                $q->whereDate('fecha_captura', '>=', $filters['fecha_inicio'])
            )
            ->when($filters['fecha_fin'], fn ($q) =>
                $q->whereDate('fecha_captura', '<=', $filters['fecha_fin'])
            )
            ->orderByDesc('fecha_captura')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Requisiciones/Index', [
            'filters' => $filters,
            'requisiciones' => [
                'data' => RequisicionResource::collection($requisiciones)->resolve(),
                'links' => $requisiciones->linkCollection(),
            ],
            'options' => [
                'empleados'   => Empleado::select('id','nombre','apellido_paterno')->get()
                    ->map(fn ($e) => [
                        'id' => $e->id,
                        'nombre_completo' => "{$e->nombre} {$e->apellido_paterno}",
                    ]),
                'proveedores' => Proveedor::select('id','nombre_comercial')->get(),
                'sucursales'  => Sucursal::select('id','nombre')->get(),
                'statuses'    => ['BORRADOR','ENVIADA','APROBADA','RECHAZADA','PAGADA'],
            ],
        ]);
    }

    // Metodo para crear una nueva requisición
    public function store(StoreRequisicionRequest $request): RedirectResponse
    {
        Requisicion::create($request->validated());

        return redirect()
            ->route('requisiciones.index')
            ->with('success', 'Requisición registrada correctamente.');
    }

    // Metodo para actualizar una requisición existente
    public function update(UpdateRequisicionRequest $request, Requisicion $requisicion): RedirectResponse
    {
        $requisicion->update($request->validated());

        return redirect()
            ->route('requisiciones.index')
            ->with('success', 'Requisición actualizada correctamente.');
    }

    // Metodo para eliminar una requisición
    public function destroy(Requisicion $requisicion): RedirectResponse
    {
        $requisicion->delete();

        return redirect()
            ->route('requisiciones.index')
            ->with('success', 'Requisición eliminada correctamente.');
    }

}
