<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Listado (si lo ocupas como catálogo o pantalla).
     * Si no tienes vista, lo puedes omitir.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $q = trim((string) $request->get('q', ''));

        $query = Proveedor::query();

        // Si NO es admin/contador, solo sus proveedores
        if (!$this->canSeeAll($user)) {
            $query->where('user_duenio_id', $user->id);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre_comercial', 'like', "%{$q}%")
                    ->orWhere('razon_social', 'like', "%{$q}%")
                    ->orWhere('rfc', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('telefono', 'like', "%{$q}%");
            });
        }

        $proveedores = $query
            ->orderBy('nombre_comercial')
            ->paginate(15)
            ->withQueryString();

        // Si tienes una pantalla Inertia, ajusta el componente:
        return inertia('Proveedores/Index', [
            'proveedores' => $proveedores,
            'filters' => ['q' => $q],
        ]);
    }

    /**
     * Store: usado por el modal “+” dentro de requisiciones.
     * Regresa redirect back con flash del ID creado.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:150'],
            'razon_social'     => ['nullable', 'string', 'max:200'],
            'rfc'              => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'contacto'         => ['nullable', 'string', 'max:150'],
            'telefono'         => ['nullable', 'string', 'max:30'],
            'email'            => ['nullable', 'email', 'max:150'],

            // Bancarios
            'beneficiario'     => ['nullable', 'string', 'max:150'],
            'banco'            => ['nullable', 'string', 'max:120'],
            'cuenta'           => ['nullable', 'string', 'max:30'],
            'clabe'            => ['nullable', 'string', 'max:30'],
        ]);

        // Normalización ligera (evita basura y retrabajo contable)
        $data['rfc'] = $this->normUpper($data['rfc'] ?? null);
        $data['clabe'] = $this->normDigits($data['clabe'] ?? null);
        $data['cuenta'] = $this->normDigits($data['cuenta'] ?? null);
        $data['telefono'] = $this->normDigits($data['telefono'] ?? null);

        $proveedor = Proveedor::create([
            'user_duenio_id'    => $user->id,
            'nombre_comercial'  => $data['nombre_comercial'],
            'razon_social'      => $data['razon_social'] ?? null,
            'rfc'               => $data['rfc'] ?? null,
            'direccion'         => $data['direccion'] ?? null,
            'contacto'          => $data['contacto'] ?? null,
            'telefono'          => $data['telefono'] ?? null,
            'email'             => $data['email'] ?? null,
            'beneficiario'      => $data['beneficiario'] ?? null,
            'banco'             => $data['banco'] ?? null,
            'cuenta'            => $data['cuenta'] ?? null,
            'clabe'             => $data['clabe'] ?? null,
        ]);

        // Flash: el Vue lo lee y auto-selecciona el proveedor recién creado.
        return redirect()
            ->back()
            ->with('success', 'Proveedor creado.')
            ->with('new_proveedor_id', $proveedor->id);
    }

    /**
     * Update: si lo editas desde su módulo.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $this->authorizeOwnerOrAdmin($request->user(), $proveedor);

        $data = $request->validate([
            'nombre_comercial' => ['required', 'string', 'max:150'],
            'razon_social'     => ['nullable', 'string', 'max:200'],
            'rfc'              => ['nullable', 'string', 'max:20'],
            'direccion'        => ['nullable', 'string', 'max:255'],
            'contacto'         => ['nullable', 'string', 'max:150'],
            'telefono'         => ['nullable', 'string', 'max:30'],
            'email'            => ['nullable', 'email', 'max:150'],

            'beneficiario'     => ['nullable', 'string', 'max:150'],
            'banco'            => ['nullable', 'string', 'max:120'],
            'cuenta'           => ['nullable', 'string', 'max:30'],
            'clabe'            => ['nullable', 'string', 'max:30'],
        ]);

        $data['rfc'] = $this->normUpper($data['rfc'] ?? null);
        $data['clabe'] = $this->normDigits($data['clabe'] ?? null);
        $data['cuenta'] = $this->normDigits($data['cuenta'] ?? null);
        $data['telefono'] = $this->normDigits($data['telefono'] ?? null);

        $proveedor->update($data);

        return redirect()->back()->with('success', 'Proveedor actualizado.');
    }

    /**
     * Destroy: evita borrar si está referenciado (si tienes FK)
     */
    public function destroy(Request $request, Proveedor $proveedor)
    {
        $this->authorizeOwnerOrAdmin($request->user(), $proveedor);

        // Reglas de negocio “no rompas contabilidad”
        if ($proveedor->requisicions()->exists()) {
            return redirect()->back()->with('error', 'No puedes eliminar: tiene requisiciones asociadas.');
        }
        if ($proveedor->comprobantes()->exists()) {
            return redirect()->back()->with('error', 'No puedes eliminar: tiene comprobantes asociados.');
        }
        if ($proveedor->gastos()->exists()) {
            return redirect()->back()->with('error', 'No puedes eliminar: tiene gastos asociados.');
        }

        $proveedor->delete();

        return redirect()->back()->with('success', 'Proveedor eliminado.');
    }

    // ==========================================================
    // Helpers
    // ==========================================================

    private function canSeeAll($user): bool
    {
        $rol = strtoupper((string) ($user->rol ?? ''));
        return in_array($rol, ['ADMIN', 'CONTADOR'], true);
    }

    private function authorizeOwnerOrAdmin($user, Proveedor $proveedor): void
    {
        if ($this->canSeeAll($user)) return;

        abort_unless((int) $proveedor->user_duenio_id === (int) $user->id, 403);
    }

    private function normUpper(?string $v): ?string
    {
        $v = trim((string) $v);
        if ($v === '') return null;
        return mb_strtoupper($v);
    }

    private function normDigits(?string $v): ?string
    {
        $v = preg_replace('/\D+/', '', (string) $v);
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }
    
}
