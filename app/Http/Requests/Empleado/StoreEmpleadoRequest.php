<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmpleadoRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    // Autoriza si el usuario puede hacer esta solicitud
    public function rules(): array
    {
        return [
            'corporativo_id' => ['required','exists:corporativos,id'],
            'sucursal_id'    => ['required','exists:sucursals,id'],
            'area_id'        => ['nullable','exists:areas,id'],

            'nombre'            => ['required','string','max:120'],
            'apellido_paterno'  => ['required','string','max:120'],
            'apellido_materno'  => ['nullable','string','max:120'],
            'email'             => ['nullable','email','max:150'],
            'telefono'          => ['nullable','string','max:30'],
            'puesto'            => ['nullable','string','max:120'],
            'activo'            => ['boolean'],

            // USER
            'user.email'    => ['required','email','unique:users,email'],
            'user.password' => ['required','min:8'],
            'user.rol'      => ['required','in:ADMIN,CONTADOR,COLABORADOR'],
        ];
    }

}
