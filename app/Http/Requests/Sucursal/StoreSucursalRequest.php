<?php

namespace App\Http\Requests\Sucursal;

use Illuminate\Foundation\Http\FormRequest;

class StoreSucursalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'corporativo_id' => ['required', 'integer', 'exists:corporativos,id'],
            'nombre'         => ['required', 'string', 'max:150'],
            'codigo'         => ['nullable', 'string', 'max:20'],
            'ciudad'         => ['nullable', 'string', 'max:120'],
            'estado'         => ['nullable', 'string', 'max:120'],
            'direccion'      => ['nullable', 'string', 'max:255'],
            'activo'         => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'nombre'    => $this->nombre ? trim($this->nombre) : null,
            'codigo'    => $this->codigo ? trim($this->codigo) : null,
            'ciudad'    => $this->ciudad ? trim($this->ciudad) : null,
            'estado'    => $this->estado ? trim($this->estado) : null,
            'direccion' => $this->direccion ? trim($this->direccion) : null,
        ]);
    }
}
