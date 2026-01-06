<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProveedorStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'nombre_comercial' => ['required', 'string', 'max:200'],
            'rfc' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],

            'beneficiario' => ['nullable', 'string', 'max:200'],
            'banco' => ['nullable', 'string', 'max:120'],
            'cuenta' => ['nullable', 'string', 'max:50'],
            'clabe' => ['nullable', 'string', 'max:30'],

            // Si quieres normalizar: ACTIVO/INACTIVO. Si no, déjalo nullable.
            'estatus' => ['nullable', 'string', 'max:20', Rule::in(['ACTIVO','INACTIVO'])],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_comercial.required' => 'El nombre comercial es obligatorio.',
            'email.email' => 'El correo no tiene un formato válido.',
            'estatus.in' => 'El estatus debe ser ACTIVO o INACTIVO.',
        ];
    }
}
