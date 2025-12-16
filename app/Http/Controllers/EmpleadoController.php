<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Sucursal;
use App\Models\Area;
use App\Models\Corporativo;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Http\Request;

class EmpleadoController extends Controller
{
    public function index(Request $request)
    {
        $q = Empleado::with(['sucursal.corporativo','area','user'])
            ->when($request->q, fn($qq) =>
                $qq->where('nombre','like',"%{$request->q}%")
                   ->orWhere('apellido_paterno','like',"%{$request->q}%")
            )
            ->when($request->sucursal_id, fn($qq) =>
                $qq->where('sucursal_id',$request->sucursal_id)
            )
            ->orderBy('nombre')
            ->paginate($request->perPage ?? 15)
            ->withQueryString();

        return Inertia::render('Empleados/Index', [
            'empleados' => $q,
            'sucursales'=> Sucursal::with('corporativo')->get(),
            'areas'     => Area::all(),
            'filters'   => $request->all(),
        ]);
    }

    public function store(StoreEmpleadoRequest $request)
    {
        DB::transaction(function () use ($request) {
            $empleado = Empleado::create($request->validated());

            User::create([
                'empleado_id' => $empleado->id,
                'name'        => "{$empleado->nombre} {$empleado->apellido_paterno}",
                'email'       => $request->user['email'],
                'password'    => bcrypt($request->user['password']),
                'rol'         => $request->user['rol'],
                'activo'      => true,
            ]);
        });

        return back()->with('success','Empleado creado');
    }

    public function update(UpdateEmpleadoRequest $request, Empleado $empleado)
    {
        $empleado->update($request->validated());
        return back()->with('success','Empleado actualizado');
    }

    public function destroy(Empleado $empleado)
    {
        $empleado->user()?->delete();
        $empleado->delete();
        return back()->with('success','Empleado eliminado');
    }

    public function bulkDestroy(Request $request)
    {
        DB::transaction(function () use ($request) {
            User::whereIn('empleado_id',$request->ids)->delete();
            Empleado::whereIn('id',$request->ids)->delete();
        });

        return back()->with('success','Empleados eliminados');
    }

}
