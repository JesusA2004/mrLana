<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Sucursal;
use App\Models\Area;
use App\Models\Corporativo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class EmpleadoController extends Controller
{
    /**
     * ======================================================
     * INDEX
     * - Eager load: sucursal.corporativo, area, user
     * - Filtros: q, corporativo_id, sucursal_id, area_id, activo
     * - Sort: nombre|id y dir asc|desc
     * - Props completas: corporativos, sucursales(con corporativo), areas
     * ======================================================
     */
    public function index(Request $request)
    {
        $perPage = (int) ($request->input('perPage', 15) ?: 15);
        $sort = $request->input('sort', 'nombre');
        $dir  = $request->input('dir', 'asc');

        $sort = in_array($sort, ['nombre', 'id'], true) ? $sort : 'nombre';
        $dir  = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

        $q = trim((string) $request->input('q', ''));

        $query = Empleado::query()
            ->with([
                'sucursal.corporativo',
                'area',
                'user',
            ])
            ->when($q !== '', function ($qq) use ($q) {
                // Agrupar ORs para no romper otros filtros
                $qq->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('apellido_paterno', 'like', "%{$q}%")
                      ->orWhere('apellido_materno', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('telefono', 'like', "%{$q}%")
                      ->orWhere('puesto', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('sucursal_id'), fn ($qq) => $qq->where('sucursal_id', $request->input('sucursal_id')))
            ->when($request->filled('area_id'), fn ($qq) => $qq->where('area_id', $request->input('area_id')))
            ->when($request->filled('activo'), fn ($qq) => $qq->where('activo', (int) $request->input('activo')))
            ->when($request->filled('corporativo_id'), function ($qq) use ($request) {
                $corpId = (int) $request->input('corporativo_id');
                // Filtra por corporativo via relación sucursal
                $qq->whereHas('sucursal', fn ($s) => $s->where('corporativo_id', $corpId));
            })
            ->orderBy($sort, $dir);

        $empleados = $query
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Empleados/Index', [
            'empleados' => $empleados,
            'corporativos' => Corporativo::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo', 'activo']),
            'sucursales' => Sucursal::query()
                ->with('corporativo:id,nombre,codigo,activo')
                ->orderBy('nombre')
                ->get(['id', 'corporativo_id', 'nombre', 'codigo', 'activo']),
            'areas' => Area::query()
                ->orderBy('nombre')
                ->get(['id', 'corporativo_id', 'nombre', 'activo']),
            'filters' => $request->all(),
        ]);
    }

    /**
     * ======================================================
     * STORE
     * - Crea empleado + user en una transacción
     * - IMPORTANTE: User fillable NO incluye rol/activo/empleado_id
     *   => usamos forceFill / asignación directa
     * ======================================================
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            // Empleado
            'sucursal_id'       => ['required', 'integer', 'exists:sucursals,id'],
            'area_id'           => ['nullable', 'integer', 'exists:areas,id'],
            'nombre'            => ['required', 'string', 'max:120'],
            'apellido_paterno'  => ['required', 'string', 'max:120'],
            'apellido_materno'  => ['nullable', 'string', 'max:120'],
            'email'             => ['nullable', 'email', 'max:150'],
            'telefono'          => ['nullable', 'string', 'max:40'],
            'puesto'            => ['nullable', 'string', 'max:150'],
            'activo'            => ['nullable', 'boolean'],

            // Datos del usuario
            'user_name'         => ['required', 'string', 'max:150'],
            'user_email'        => ['required', 'email', 'max:150'],
            'user_password'     => ['required', 'string', 'min:6'],
            'user_rol'          => ['required', 'in:ADMIN,CONTADOR,COLABORADOR'],
            'user_activo'       => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data) {
            $empleado = Empleado::create([
                'sucursal_id'      => (int) $data['sucursal_id'],
                'area_id'          => $data['area_id'] !== null ? (int) $data['area_id'] : null,
                'nombre'           => $data['nombre'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'] ?? null,
                'email'            => $data['email'] ?? null,
                'telefono'         => $data['telefono'] ?? null,
                'puesto'           => $data['puesto'] ?? null,
                'activo'           => (bool) ($data['activo'] ?? true),
            ]);

            $user = new User();
            $user->name = $data['user_name'];
            $user->email = $data['user_email'];
            $user->password = Hash::make($data['user_password']); // explícito
            // columnas extra (no están en fillable) => asignación directa
            $user->empleado_id = $empleado->id;
            $user->rol = $data['user_rol'];
            $user->activo = (bool) ($data['user_activo'] ?? true);
            $user->save();
        });

        return back()->with('success', 'Empleado creado');
    }

    /**
     * ======================================================
     * UPDATE
     * - Actualiza empleado + usuario
     * - Password solo si viene
     * ======================================================
     */
    public function update(Request $request, Empleado $empleado)
    {
        $data = $request->validate([
            // Empleado
            'sucursal_id'       => ['required', 'integer', 'exists:sucursals,id'],
            'area_id'           => ['nullable', 'integer', 'exists:areas,id'],
            'nombre'            => ['required', 'string', 'max:120'],
            'apellido_paterno'  => ['required', 'string', 'max:120'],
            'apellido_materno'  => ['nullable', 'string', 'max:120'],
            'email'             => ['nullable', 'email', 'max:150'],
            'telefono'          => ['nullable', 'string', 'max:40'],
            'puesto'            => ['nullable', 'string', 'max:150'],
            'activo'            => ['nullable', 'boolean'],

            // User
            'user_name'         => ['required', 'string', 'max:150'],
            'user_email'        => ['required', 'email', 'max:150'],
            'user_password'     => ['nullable', 'string', 'min:6'],
            'user_rol'          => ['required', 'in:ADMIN,CONTADOR,COLABORADOR'],
            'user_activo'       => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($data, $empleado) {
            $empleado->update([
                'sucursal_id'      => (int) $data['sucursal_id'],
                'area_id'          => $data['area_id'] !== null ? (int) $data['area_id'] : null,
                'nombre'           => $data['nombre'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'] ?? null,
                'email'            => $data['email'] ?? null,
                'telefono'         => $data['telefono'] ?? null,
                'puesto'           => $data['puesto'] ?? null,
                'activo'           => (bool) ($data['activo'] ?? true),
            ]);

            $user = $empleado->user()->first() ?? new User();

            // si el usuario no existía, lo ligamos
            $user->empleado_id = $empleado->id;

            // name/email/password (fillable) + extras (directo)
            $user->name = $data['user_name'];
            $user->email = $data['user_email'];
            $user->rol = $data['user_rol'];
            $user->activo = (bool) ($data['user_activo'] ?? true);

            if (!empty($data['user_password'])) {
                $user->password = Hash::make($data['user_password']);
            }

            $user->save();
        });

        return back()->with('success', 'Empleado actualizado');
    }

    /**
     * ======================================================
     * DESTROY
     * - Borra user ligado y luego empleado
     * ======================================================
     */
    public function destroy(Empleado $empleado)
    {
        DB::transaction(function () use ($empleado) {
            $empleado->user()?->delete();
            $empleado->delete();
        });

        return back()->with('success', 'Empleado eliminado');
    }

    /**
     * ======================================================
     * BULK DESTROY
     * ======================================================
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data) {
            User::whereIn('empleado_id', $data['ids'])->delete();
            Empleado::whereIn('id', $data['ids'])->delete();
        });

        return back()->with('success', 'Empleados eliminados');
    }

}
