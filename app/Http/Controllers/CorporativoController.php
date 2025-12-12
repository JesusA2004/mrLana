<?php

namespace App\Http\Controllers;

use App\Models\Corporativo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Http\Resources\CorporativoResource;
use App\Http\Requests\Corporativo\StoreCorporativoRequest;
use App\Http\Requests\Corporativo\UpdateCorporativoRequest;

class CorporativoController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $activo  = (string) $request->query('activo', 'all');
        $perPage = (int) $request->query('per_page', 10);
        $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 10;

        $query = Corporativo::query()->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'like', "%{$q}%")
                  ->orWhere('rfc', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('telefono', 'like', "%{$q}%")
                  ->orWhere('codigo', 'like', "%{$q}%");
            });
        }

        if ($activo === '1' || $activo === '0') {
            $query->where('activo', $activo === '1');
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Corporativos/Index', [
            'corporativos' => [
                'data' => CorporativoResource::collection($paginator)->resolve(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                    'links'        => $paginator->linkCollection(),
                ],
            ],
            'filters' => [
                'q'        => $q,
                'activo'   => $activo,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function store(StoreCorporativoRequest $request)
    {
        $data = $request->validated();

        // ✅ Si llega archivo directo (opcional)
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('corporativos/logos', 'public');
            $data['logo_path'] = Storage::url($path); // /storage/...
        }

        unset($data['logo']); // no es columna

        // ✅ IMPORTANTE: aquí ya viene logo_path si tu front lo manda
        Corporativo::create($data);

        return redirect()
            ->route('corporativos.index')
            ->with('success', 'Corporativo creado correctamente.');
    }

    public function update(UpdateCorporativoRequest $request, Corporativo $corporativo)
    {
        $data = $request->validated();

        if (!$request->has('activo')) {
            unset($data['activo']);
        }

        $oldLogo = $corporativo->logo_path;

        // ✅ Si llega archivo directo (opcional)
        if ($request->hasFile('logo')) {
            $this->deletePublicLogoIfExists($oldLogo);

            $path = $request->file('logo')->store('corporativos/logos', 'public');
            $data['logo_path'] = Storage::url($path);
        }

        // ✅ Si llega logo_path (tu flujo real)
        if (array_key_exists('logo_path', $data)) {
            $newLogo = $data['logo_path'];

            // Cambió por otra ruta -> borra el anterior
            if ($newLogo && $newLogo !== $oldLogo) {
                $this->deletePublicLogoIfExists($oldLogo);
            }

            // Se quitó el logo -> borra el anterior
            if ($newLogo === null && $oldLogo) {
                $this->deletePublicLogoIfExists($oldLogo);
            }
        }

        unset($data['logo']);

        $corporativo->update($data);

        return redirect()
            ->route('corporativos.index')
            ->with('success', 'Corporativo actualizado correctamente.');
    }

    public function destroy(Corporativo $corporativo)
    {
        $this->deletePublicLogoIfExists($corporativo->logo_path);

        $corporativo->delete();

        return redirect()
            ->route('corporativos.index')
            ->with('success', 'Corporativo eliminado correctamente.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $path = $request->file('logo')->store('corporativos/logos', 'public');

        return response()->json([
            'logo_path' => Storage::url($path), // /storage/...
        ]);
    }

    private function deletePublicLogoIfExists(?string $logoPath): void
    {
        if (!$logoPath) return;

        $clean = str_starts_with($logoPath, '/storage/')
            ? substr($logoPath, 9)
            : (str_starts_with($logoPath, 'storage/') ? substr($logoPath, 8) : null);

        if ($clean) {
            Storage::disk('public')->delete($clean);
        }
    }

}
