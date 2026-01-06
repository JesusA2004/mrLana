<?php

namespace App\Http\Controllers;

use App\Http\Requests\Proveedor\BulkDestroyRequest;
use App\Http\Requests\Proveedor\ProveedorIndexRequest;
use App\Http\Requests\Proveedor\ProveedorStoreRequest;
use App\Http\Requests\Proveedor\ProveedorUpdateRequest;
use App\Http\Resources\ProveedorResource;
use App\Models\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class ProveedorController extends Controller {

    /**
     * Listado de proveedores con:
     * - Multi-tenant: ADMIN ve todo, demás solo lo propio
     * - Filtros: q, estatus (si existe columna)
     * - Orden: whitelist (sort/dir)
     * - Paginación: perPage
     *
     * Nota de naming:
     * - En BD la tabla se llama "proveedors".
     * - En el front (Inertia) la página se llama "Proveedores/Index".
     */
    public function index(ProveedorIndexRequest $request): Response
    {
        $user = $request->user();
        $q = $request->validated();

        $query = Proveedor::query();

        // ============================
        // Multi-tenant simple
        // ============================
        if (($user->rol ?? null) !== 'ADMIN') {
            // Si tienes scope ownedBy(), úsalo.
            // Si no, este where es suficiente.
            if (method_exists(Proveedor::class, 'scopeOwnedBy')) {
                $query->ownedBy($user->id);
            } else {
                $query->where('user_duenio_id', $user->id);
            }
        }

        // ============================
        // Búsqueda libre
        // ============================
        if (!empty($q['q'])) {
            $term = trim((string) $q['q']);

            $query->where(function ($sub) use ($term) {
                $sub->where('nombre_comercial', 'like', "%{$term}%")
                    ->orWhere('rfc', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('beneficiario', 'like', "%{$term}%")
                    ->orWhere('banco', 'like', "%{$term}%");
            });
        }

        // ============================
        // Filtro estatus (blindado)
        // ============================
        $hasEstatusColumn = Schema::hasColumn('proveedors', 'estatus');

        if (!empty($q['estatus']) && $hasEstatusColumn) {
            $query->where('estatus', $q['estatus']); // parametrizado
        }

        // ============================
        // Orden seguro (whitelist)
        // ============================
        $allowedSort = ['created_at', 'nombre_comercial', 'estatus'];

        $sort = $q['sort'] ?? 'created_at';
        $sort = in_array($sort, $allowedSort, true) ? $sort : 'created_at';

        // Si piden ordenar por estatus pero no existe la columna, caemos a created_at
        if ($sort === 'estatus' && !$hasEstatusColumn) {
            $sort = 'created_at';
        }

        $dir = strtolower((string) ($q['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        // ============================
        // Paginación
        // ============================
        $perPage = (int) ($q['perPage'] ?? 10);
        $perPage = $perPage > 0 ? $perPage : 10;

        $rows = $query
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        // ============================
        // Permisos UI (ajusta tu política)
        // ============================
        $canDelete = in_array(($user->rol ?? 'COLABORADOR'), ['ADMIN'], true);

        return Inertia::render('Proveedores/Index', [
            'filters' => [
                'q' => $q['q'] ?? '',
                // si no existe columna, devolvemos vacío para que el front no “fuerce” el filtro
                'estatus' => $hasEstatusColumn ? ($q['estatus'] ?? '') : '',
                'sort' => $sort,
                'dir' => $dir,
                'perPage' => $perPage,
            ],
            'rows' => ProveedorResource::collection($rows),
            'canDelete' => $canDelete,

            // opcional: le puedes decir al front si existe la columna
            // 'hasEstatus' => $hasEstatusColumn,
        ]);
    }

    /**
     * Crear proveedor
     * - fuerza user_duenio_id al usuario actual
     */
    public function store(ProveedorStoreRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $data['user_duenio_id'] = $user->id;

        Proveedor::create($data);

        return back()->with('success', 'Proveedor creado.');
    }

    /**
     * Actualizar proveedor
     * - ADMIN puede editar todo
     * - los demás solo si es dueño
     */
    public function update(ProveedorUpdateRequest $request, Proveedor $proveedor): RedirectResponse
    {
        $user = $request->user();

        if (($user->rol ?? null) !== 'ADMIN' && (int) $proveedor->user_duenio_id !== (int) $user->id) {
            abort(403);
        }

        $proveedor->update($request->validated());

        return back()->with('success', 'Proveedor actualizado.');
    }

    /**
     * Eliminar proveedor
     * - ADMIN puede eliminar todo
     * - los demás solo si es dueño
     */
    public function destroy(ProveedorUpdateRequest $request, Proveedor $proveedor): RedirectResponse
    {
        // Nota: aquí uso Request tipado por consistencia, pero también podría ser Request normal.
        $user = $request->user();

        if (($user->rol ?? null) !== 'ADMIN' && (int) $proveedor->user_duenio_id !== (int) $user->id) {
            abort(403);
        }

        $proveedor->delete();

        return back()->with('success', 'Proveedor eliminado.');
    }

    /**
     * Eliminar múltiples proveedores
     * - ADMIN elimina cualquier id
     * - los demás solo sus ids
     */
    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        $user = $request->user();
        $ids = $request->validated('ids');

        $query = Proveedor::query()->whereIn('id', $ids);

        if (($user->rol ?? null) !== 'ADMIN') {
            $query->where('user_duenio_id', $user->id);
        }

        $query->delete();

        return back()->with('success', 'Proveedores eliminados.');
    }

}
