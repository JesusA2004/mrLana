<?php

namespace App\Http\Controllers;

use App\Http\Requests\Concepto\StoreConceptoRequest;
use App\Http\Requests\Concepto\UpdateConceptoRequest;
use App\Models\Concepto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConceptoController extends Controller
{

    // Metodo para listar los conceptos con filtros y paginacion
    public function index(Request $request)
    {
        $perPage = (int) ($request->integer('perPage') ?: 15);
        $perPage = max(5, min(100, $perPage));

        $q = Concepto::query();

        if ($search = trim((string) $request->get('q', ''))) {
            $q->where(function ($w) use ($search) {
                $w->where('nombre', 'like', "%{$search}%");
            });
        }

        if (($activo = $request->get('activo', '')) !== '') {
            $q->where('activo', (bool) (int) $activo);
        }

        $sort = $request->get('sort', 'id');
        $dir  = $request->get('dir', 'desc');

        $sort = in_array($sort, ['id', 'nombre'], true) ? $sort : 'id';
        $dir  = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $conceptos = $q->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Conceptos/Index', [
            'conceptos' => $conceptos,
            'filters'   => $request->only(['q', 'activo', 'perPage', 'sort', 'dir']),
        ]);
    }

    // Metodo para crear un nuevo concepto
    public function store(StoreConceptoRequest $request)
    {
        DB::transaction(function () use ($request) {
            Concepto::create([
                'nombre' => $request->validated('nombre'),
                'activo' => (bool) ($request->validated('activo') ?? true),
            ]);
        });

        return back()->with('success', 'Concepto creado.');
    }

    // Metodo para actualizar un concepto
    public function update(UpdateConceptoRequest $request, Concepto $concepto)
    {
        DB::transaction(function () use ($request, $concepto) {
            $concepto->update([
                'nombre' => $request->validated('nombre'),
                'activo' => (bool) ($request->validated('activo') ?? $concepto->activo),
            ]);
        });

        return back()->with('success', 'Concepto actualizado.');
    }

    // Metodo para eliminar un solo concepto
    public function destroy(Concepto $concepto)
    {
        DB::transaction(function () use ($concepto) {
            $concepto->delete();
        });

        return back()->with('success', 'Concepto eliminado.');
    }


    // Metodo para eliminar multiples conceptos
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids', []);

        if (!is_array($ids) || empty($ids)) {
            return back()->with('error', 'No se recibieron IDs.');
        }

        DB::transaction(function () use ($ids) {
            Concepto::whereIn('id', $ids)->delete();
        });

        return back()->with('success', 'Conceptos eliminados.');
    }

}

/**
 * Conceptos (Grupos):
 * Administrativo
 * Comercial
 * Community Manager
 * Compras
 * Contabilidad
 * Dirección
 * Diseño
 * General
 * Operación
 * Recursos Humanos
 * Sistemas
 * Ventas
 */
