<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Corporativo;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $corporativoId = $request->get('corporativo_id', '');
        $activo = $request->get('activo', ''); // '' | '1' | '0'
        $perPage = (int) $request->get('perPage', 15);

        $sort = $request->get('sort', 'nombre'); // nombre | id
        $dir  = $request->get('dir', 'asc');     // asc | desc

        if (!in_array($sort, ['nombre', 'id'], true)) $sort = 'nombre';
        if (!in_array($dir, ['asc', 'desc'], true)) $dir = 'asc';
        if ($perPage < 10) $perPage = 10;

        $areas = Area::query()
            ->with(['corporativo:id,nombre,codigo,activo'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('nombre', 'like', "%{$q}%");
            })
            ->when($corporativoId !== '' && $corporativoId !== null, function ($query) use ($corporativoId) {
                $query->where('corporativo_id', (int) $corporativoId);
            })
            ->when($activo !== '' && $activo !== null, function ($query) use ($activo) {
                $query->where('activo', (int) $activo);
            });

        /**
         * Orden “enterprise”:
         * 1) corporativo (para agrupar desde inicio)
         * 2) sort elegido (nombre/id) con dir
         * 3) fallback por id
         */
        $areas->orderByRaw('COALESCE(corporativo_id, 0) asc');

        if ($sort === 'nombre') {
            $areas->orderBy('nombre', $dir);
        } else {
            $areas->orderBy('id', $dir);
        }

        $areas->orderBy('id', 'asc');

        $paginated = $areas
            ->paginate($perPage)
            ->withQueryString();

        // corporativos para filtros y select modal
        $corporativos = Corporativo::query()
            ->select(['id','nombre','codigo','activo'])
            ->orderBy('nombre')
            ->get();

        return inertia('Areas/Index', [
            'areas' => $paginated,
            'corporativos' => $corporativos,
            'filters' => [
                'q' => $q,
                'corporativo_id' => $corporativoId,
                'activo' => $activo,
                'perPage' => $perPage,
                'sort' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'corporativo_id' => ['nullable','integer','exists:corporativos,id'],
            'nombre' => ['required','string','max:150'],
            'activo' => ['required','boolean'],
        ]);

        Area::create($data);

        return back();
    }

    public function update(Request $request, Area $area)
    {
        $data = $request->validate([
            'corporativo_id' => ['nullable','integer','exists:corporativos,id'],
            'nombre' => ['required','string','max:150'],
            'activo' => ['required','boolean'],
        ]);

        $area->update($data);

        return back();
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return back();
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer'],
        ]);

        // Si quieres: aquí podrías checar permisos/tenant/corporativo
        \App\Models\Area::query()
            ->whereIn('id', $data['ids'])
            ->delete();

        return back();
    }
    
}
