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
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\EmpleadoAccesoCreadoMail;

class EmpleadoController extends Controller
{
    /**
     * =========================================================================
     * INDEX
     * -------------------------------------------------------------------------
     * Listado enterprise:
     * - Eager load: sucursal.corporativo, area, user
     * - Filtros: q, corporativo_id, sucursal_id, area_id, activo ('all'|'1'|'0')
     * - Paginación: per_page (snake) + compat perPage
     * - Sort: nombre|id con dir asc|desc
     * =========================================================================
     */
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        // filtros
        $corporativoId = $request->get('corporativo_id', '');
        $sucursalId    = $request->get('sucursal_id', '');
        $areaId        = $request->get('area_id', '');

        // activo: 'all'|'1'|'0' (acepta '' por compat => all)
        $activo = $request->get('activo', 'all');
        $activo = ($activo === '' || $activo === null) ? 'all' : (string) $activo;

        // per_page (snake) preferido; compat perPage
        $perPage = (int) ($request->get('per_page', $request->get('perPage', 15)));
        $perPage = max(10, min(100, $perPage));

        // sort/dir
        $sort = (string) $request->get('sort', 'nombre');
        $dir  = (string) $request->get('dir', 'asc');

        $sort = in_array($sort, ['nombre', 'id'], true) ? $sort : 'nombre';
        $dir  = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

        // normaliza IDs
        $corporativoId = ($corporativoId === '' || $corporativoId === null) ? null : (int) $corporativoId;
        $sucursalId    = ($sucursalId === '' || $sucursalId === null) ? null : (int) $sucursalId;
        $areaId        = ($areaId === '' || $areaId === null) ? null : (int) $areaId;

        $query = Empleado::query()
            ->with([
                'sucursal:id,corporativo_id,nombre,codigo,activo',
                'sucursal.corporativo:id,nombre,codigo,activo',
                'area:id,corporativo_id,nombre,activo',
                'user:id,empleado_id,name,email,rol,activo',
            ])
            ->when($q !== '', function ($qq) use ($q) {
                // Agrupa ORs para no romper filtros
                $qq->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('apellido_paterno', 'like', "%{$q}%")
                      ->orWhere('apellido_materno', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%")
                      ->orWhere('telefono', 'like', "%{$q}%")
                      ->orWhere('puesto', 'like', "%{$q}%");
                });
            })
            ->when(!is_null($sucursalId), fn ($qq) => $qq->where('sucursal_id', $sucursalId))
            ->when(!is_null($areaId), fn ($qq) => $qq->where('area_id', $areaId))
            ->when($activo !== 'all', fn ($qq) => $qq->where('activo', (int) $activo))
            ->when(!is_null($corporativoId), function ($qq) use ($corporativoId) {
                // Filtra por corporativo via sucursal
                $qq->whereHas('sucursal', fn ($s) => $s->where('corporativo_id', $corporativoId));
            });

        /**
         * Orden enterprise:
         * 1) sucursal_id para agrupar visualmente
         * 2) sort elegido
         * 3) fallback id asc
         */
        $query->orderByRaw('COALESCE(sucursal_id, 0) asc');
        $query->orderBy($sort, $dir);
        $query->orderBy('id', 'asc');

        $empleados = $query->paginate($perPage)->withQueryString();

        return Inertia::render('Empleados/Index', [
            'empleados' => $empleados,

            // datasets para filtros/modales
            'corporativos' => Corporativo::query()
                ->select(['id', 'nombre', 'codigo', 'activo'])
                ->orderBy('nombre')
                ->get(),

            'sucursales' => Sucursal::query()
                ->with('corporativo:id,nombre,codigo,activo')
                ->select(['id', 'corporativo_id', 'nombre', 'codigo', 'activo'])
                ->orderBy('nombre')
                ->get(),

            'areas' => Area::query()
                ->select(['id', 'corporativo_id', 'nombre', 'activo'])
                ->orderBy('nombre')
                ->get(),

            // filtros normalizados (snake + compat)
            'filters' => [
                'q' => $q,
                'corporativo_id' => $corporativoId,
                'sucursal_id' => $sucursalId,
                'area_id' => $areaId,
                'activo' => $activo,
                'per_page' => $perPage,
                'perPage' => $perPage,
                'sort' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    /**
     * =========================================================================
     * STORE
     * -------------------------------------------------------------------------
     * Crea Empleado + User (opcional pero aquí lo pides obligatorio) en transacción.
     * - Empleado.activo default true
     * - User.activo default true
     * - Regla: si la sucursal está en baja, NO permitir alta de empleado.
     * =========================================================================
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            // Empleado (NO pedimos email aquí; se llenará con user_email)
            'sucursal_id'       => ['required', 'integer', 'exists:sucursals,id'],
            'area_id'           => ['nullable', 'integer', 'exists:areas,id'],
            'nombre'            => ['required', 'string', 'max:120'],
            'apellido_paterno'  => ['required', 'string', 'max:120'],
            'apellido_materno'  => ['nullable', 'string', 'max:120'],
            'telefono'          => ['nullable', 'string', 'max:40'],
            'puesto'            => ['nullable', 'string', 'max:150'],
            'activo'            => ['nullable', 'boolean'],

            // User (email único que se usa para ambos)
            'user_name'         => ['required', 'string', 'max:150'],
            'user_email'        => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'user_rol'          => ['required', 'in:ADMIN,CONTADOR,COLABORADOR'],
            'user_activo'       => ['nullable', 'boolean'],
        ]);

        // Bloqueo: no dar de alta empleado si sucursal está en baja (y opcionalmente corporativo)
        $sucursal = Sucursal::query()
            ->select(['id', 'activo', 'corporativo_id'])
            ->with(['corporativo:id,activo'])
            ->find((int) $data['sucursal_id']);

        if (!$sucursal) {
            return back()->withErrors(['sucursal_id' => 'Sucursal no encontrada.']);
        }

        if (!$sucursal->activo) {
            return back()->withErrors([
                'sucursal_id' => 'No puedes crear empleados en una sucursal dada de baja.',
            ]);
        }

        // Si tu Sucursal tiene relación corporativo y quieres blindar también:
        if ($sucursal->relationLoaded('corporativo') && $sucursal->corporativo && !$sucursal->corporativo->activo) {
            return back()->withErrors([
                'sucursal_id' => 'No puedes crear empleados en un corporativo dado de baja.',
            ]);
        }

        // Password 8 alfanumérica sin símbolos raros (A-Z0-9)
        $plainPassword = Str::upper(Str::random(8));

        try {
            DB::transaction(function () use ($data, $plainPassword) {

                // 1) Empleado (email = user_email)
                $empleado = Empleado::create([
                    'sucursal_id'      => (int) $data['sucursal_id'],
                    'area_id'          => $data['area_id'] !== null ? (int) $data['area_id'] : null,
                    'nombre'           => $data['nombre'],
                    'apellido_paterno' => $data['apellido_paterno'],
                    'apellido_materno' => $data['apellido_materno'] ?? null,
                    'email'            => $data['user_email'], // MISMO EMAIL (no se pide doble en front)
                    'telefono'         => $data['telefono'] ?? null,
                    'puesto'           => $data['puesto'] ?? null,
                    'activo'           => (bool) ($data['activo'] ?? true),
                ]);

                // 2) User (email = user_email, password generado)
                $user = new User();
                $user->name = $data['user_name'];
                $user->email = $data['user_email'];
                $user->password = Hash::make($plainPassword);

                // extras
                $user->empleado_id = $empleado->id;
                $user->rol = $data['user_rol'];
                $user->activo = (bool) ($data['user_activo'] ?? true);

                $user->save();

                // 3) Email de acceso (password en el correo)
                Mail::to($user->email)->send(new EmpleadoAccesoCreadoMail($user, $plainPassword));
            });

            return back()->with('success', 'Empleado creado. Se envió la contraseña por correo.');
        } catch (\Throwable $e) {
            report($e);

            return back()->withErrors([
                'user_email' => 'No se pudo crear el empleado. Revisa el correo o la configuración de envío.',
            ]);
        }
    }

    /**
     * =========================================================================
     * UPDATE
     * -------------------------------------------------------------------------
     * Actualiza Empleado + User.
     * - Password solo si viene.
     * - Si cambias a una sucursal inactiva, bloquea.
     * - Nota negocio: el estatus (baja/reactivar) se maneja por destroy/activate.
     * =========================================================================
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

            // opcional, pero lo respetamos si lo mandas
            'activo'            => ['nullable', 'boolean'],

            // User
            'user_name'         => ['required', 'string', 'max:150'],
            'user_email'        => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore(optional($empleado->user)->id),
            ],
            'user_password'     => ['nullable', 'string', 'min:6'],
            'user_rol'          => ['required', 'in:ADMIN,CONTADOR,COLABORADOR'],
            'user_activo'       => ['nullable', 'boolean'],
        ]);

        // Bloqueo: no mover/editar a una sucursal inactiva
        $sucursal = Sucursal::query()->select(['id', 'activo'])->find((int) $data['sucursal_id']);
        if ($sucursal && !$sucursal->activo) {
            return back()->withErrors([
                'sucursal_id' => 'No puedes asignar un empleado a una sucursal dada de baja.',
            ]);
        }

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

                // Si no lo mandas, conserva.
                'activo'           => array_key_exists('activo', $data) ? (bool) $data['activo'] : $empleado->activo,
            ]);

            $user = $empleado->user()->first() ?? new User();

            $user->empleado_id = $empleado->id;
            $user->name = $data['user_name'];
            $user->email = $data['user_email'];
            $user->rol = $data['user_rol'];

            // Si no mandas user_activo, conserva.
            if (array_key_exists('user_activo', $data)) {
                $user->activo = (bool) $data['user_activo'];
            }

            if (!empty($data['user_password'])) {
                $user->password = Hash::make($data['user_password']);
            }

            $user->save();
        });

        return back()->with('success', 'Empleado actualizado.');
    }

    /**
     * =========================================================================
     * DESTROY (baja lógica)
     * -------------------------------------------------------------------------
     * - NO elimina físicamente.
     * - Si ya está inactivo => responde OK.
     * - También desactiva el User ligado (si existe).
     * =========================================================================
     */
    public function destroy(Empleado $empleado)
    {
        if (!$empleado->activo) {
            return back()->with('success', 'El empleado ya se encontraba dado de baja.');
        }

        DB::transaction(function () use ($empleado) {
            $empleado->update(['activo' => false]);

            // Si tiene usuario, lo desactivamos también (sin borrarlo)
            $empleado->user()->update(['activo' => false]);
        });

        return back()->with('success', 'Empleado dado de baja correctamente.');
    }

    /**
     * =========================================================================
     * ACTIVATE (PATCH)
     * -------------------------------------------------------------------------
     * Reactiva empleado + user.
     * Regla: si la sucursal está en baja => NO activar.
     * =========================================================================
     */
    public function activate(Request $request, Empleado $empleado)
    {
        $sucursal = Sucursal::query()->select(['id', 'activo'])->find((int) $empleado->sucursal_id);
        if ($sucursal && !$sucursal->activo) {
            return back()->withErrors([
                'sucursal_id' => 'No puedes activar un empleado si su sucursal está dada de baja.',
            ]);
        }

        DB::transaction(function () use ($empleado) {
            $empleado->update(['activo' => true]);
            $empleado->user()->update(['activo' => true]);
        });

        return back()->with('success', 'Empleado activado.');
    }

    /**
     * =========================================================================
     * BULK DESTROY (baja lógica masiva)
     * -------------------------------------------------------------------------
     * - Marca empleados.activo = false
     * - Marca users.activo = false para los ligados
     * =========================================================================
     */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', Rule::exists('empleados', 'id')],
        ]);

        DB::transaction(function () use ($data) {
            Empleado::query()
                ->whereIn('id', $data['ids'])
                ->update(['activo' => false]);

            User::query()
                ->whereIn('empleado_id', $data['ids'])
                ->update(['activo' => false]);
        });

        return back()->with('success', 'Empleados dados de baja.');
    }
}
