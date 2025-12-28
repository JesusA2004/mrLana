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

class EmpleadoController extends Controller {

    // Metodo para listar empleados con filtros, paginación y ordenamiento
    public function index(Request $request){
        // =========================
        // Normalización de filtros
        // =========================
        $q = trim((string) $request->get('q', ''));

        $corporativoId = $request->get('corporativo_id', '');
        $sucursalId    = $request->get('sucursal_id', '');
        $areaId        = $request->get('area_id', '');

        // activo: 'all'|'1'|'0' (si viene vacío => all)
        $activo = $request->get('activo', '1');
        $activo = ($activo === '' || $activo === null) ? 'all' : (string) $activo;
        $activo = in_array($activo, ['all', '1', '0'], true) ? $activo : '1';

        // per_page (snake) preferido; compat perPage
        $perPage = (int) ($request->get('per_page', $request->get('perPage', 15)));
        $perPage = max(10, min(100, $perPage));

        // sort/dir
        $sort = (string) $request->get('sort', 'nombre');
        $dir  = (string) $request->get('dir', 'asc');
        $sort = in_array($sort, ['nombre', 'id'], true) ? $sort : 'nombre';
        $dir  = in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc';

        // IDs
        $corporativoId = ($corporativoId === '' || $corporativoId === null) ? null : (int) $corporativoId;
        $sucursalId    = ($sucursalId === '' || $sucursalId === null) ? null : (int) $sucursalId;
        $areaId        = ($areaId === '' || $areaId === null) ? null : (int) $areaId;

        // Para que la paginación SIEMPRE conserve los filtros (links con querystring)
        $appends = [
            'q'              => $q,
            'corporativo_id' => $corporativoId ?? '',
            'sucursal_id'    => $sucursalId ?? '',
            'area_id'        => $areaId ?? '',
            'activo'         => $activo,
            'per_page'       => $perPage,
            'sort'           => $sort,
            'dir'            => $dir,
        ];

        // =========================
        // Query
        // =========================
        $query = Empleado::query()
            ->with([
                'sucursal:id,corporativo_id,nombre,codigo,activo',
                'sucursal.corporativo:id,nombre,codigo,activo',
                'area:id,corporativo_id,nombre,activo',
                'user:id,empleado_id,name,email,rol,activo',
            ])
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido_paterno', 'like', "%{$q}%")
                        ->orWhere('apellido_materno', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%")
                        ->orWhere('puesto', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('email', 'like', "%{$q}%")
                            ->orWhere('name', 'like', "%{$q}%");
                        })
                        ->orWhereHas('sucursal', fn ($s) => $s->where('nombre', 'like', "%{$q}%"))
                        ->orWhereHas('area', fn ($a) => $a->where('nombre', 'like', "%{$q}%"));
                });
            })
            ->when(!is_null($sucursalId), fn ($qq) => $qq->where('sucursal_id', $sucursalId))
            ->when(!is_null($areaId), fn ($qq) => $qq->where('area_id', $areaId))
            ->when($activo !== 'all', fn ($qq) => $qq->where('activo', (int) $activo))
            ->when(!is_null($corporativoId), function ($qq) use ($corporativoId) {
                $qq->whereHas('sucursal', fn ($s) => $s->where('corporativo_id', $corporativoId));
            });

        // Orden (A-Z / Z-A)
        if ($sort === 'id') {
            $query->orderBy('id', $dir);
        } else {
            // "nombre" = orden enterprise (apellidos y nombre)
            $query->orderBy('apellido_paterno', $dir);
            $query->orderByRaw("COALESCE(apellido_materno, '') {$dir}");
            $query->orderBy('nombre', $dir);
        }

        // fallback estable
        $query->orderBy('id', 'asc');

        // 2) Orden principal
        if ($sort === 'id') {
            $query->orderBy('id', $dir);
        } else {
            // "nombre" = nombre completo enterprise (apellido paterno, materno, nombre)
            $query->orderBy('apellido_paterno', $dir);
            $query->orderByRaw('COALESCE(apellido_materno, \'\') ' . $dir);
            $query->orderBy('nombre', $dir);
        }

        // 3) Fallback estable (evita “brincos”)
        $query->orderBy('id', 'asc');

        $empleados = $query->paginate($perPage)->appends($appends);

        // =========================
        // Response Inertia
        // =========================
        return Inertia::render('Empleados/Index', [
            'empleados' => $empleados,

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

            // OJO: devuelve EXACTO lo que tu hook consume
            'filters' => [
                'q'              => $q,
                'corporativo_id' => $corporativoId,
                'sucursal_id'    => $sucursalId,
                'area_id'        => $areaId,
                'activo'         => $activo,
                'per_page'       => $perPage,
                'perPage'        => $perPage, // compat
                'sort'           => $sort,
                'dir'            => $dir,
            ],
        ]);
    }

    // Metodo para registrar un nuevo empleado
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

        $this->assertOrgActivaOrFail((int)$data['sucursal_id'], $data['area_id'] !== null ? (int)$data['area_id'] : null);

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

    // Metodo para actualizar un empleado existente
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
                'email'            => $data['email'],
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

    // Metodo para dar de baja (lógica) a un empleado
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

    // Metodo para reactivar un empleado dado de baja
    public function activate(Request $request, Empleado $empleado)
    {
        $sucursal = Sucursal::query()->select(['id', 'activo'])->find((int) $empleado->sucursal_id);
        if ($sucursal && !$sucursal->activo) {
            return back()->withErrors([
                'sucursal_id' => 'No puedes activar un empleado si su sucursal está dada de baja.',
            ]);
        }

        $this->assertOrgActivaOrFail((int)$empleado->sucursal_id, $empleado->area_id ? (int)$empleado->area_id : null);

        DB::transaction(function () use ($empleado) {
            $empleado->update(['activo' => true]);
            $empleado->user()->update(['activo' => true]);
        });

        return back()->with('success', 'Empleado activado.');
    }

    // Metodo para dar de baja (lógica) varios empleados
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

    // Metodo para reactivar varios empleados dados de baja
    private function assertOrgActivaOrFail(int $sucursalId, ?int $areaId = null): void
    {
        $sucursal = Sucursal::query()
            ->select(['id','corporativo_id','activo'])
            ->with(['corporativo:id,activo'])
            ->find($sucursalId);

        if (!$sucursal) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sucursal_id' => 'Sucursal no encontrada.',
            ]);
        }

        if (!$sucursal->activo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'sucursal_id' => 'La sucursal está dada de baja. Reactívala para poder continuar.',
            ]);
        }

        if ($sucursal->corporativo && !$sucursal->corporativo->activo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'corporativo_id' => 'El corporativo está dado de baja. Reactívalo para poder continuar.',
            ]);
        }

        if (!is_null($areaId)) {
            $area = Area::query()
                ->select(['id','corporativo_id','activo'])
                ->find($areaId);

            if (!$area) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'area_id' => 'Área no encontrada.',
                ]);
            }

            if (!$area->activo) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'area_id' => 'El área está dada de baja. Reactívala para poder continuar.',
                ]);
            }

            // Debe pertenecer al mismo corporativo de la sucursal
            if ((int)$area->corporativo_id !== (int)$sucursal->corporativo_id) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'area_id' => 'El área no pertenece al corporativo seleccionado.',
                ]);
            }
        }
}
    
}
