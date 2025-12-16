<?php

namespace App\Http\Requests\Empleado;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmpleadoRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sucursal_id' => ['required','exists:sucursals,id'],
            'area_id'     => ['nullable','exists:areas,id'],

            'nombre'           => ['required','string','max:120'],
            'apellido_paterno' => ['required','string','max:120'],
            'apellido_materno' => ['nullable','string','max:120'],
            'email'            => ['nullable','email','max:150'],
            'telefono'         => ['nullable','string','max:30'],
            'puesto'           => ['nullable','string','max:120'],
            'activo'           => ['boolean'],
        ];
    }

}
