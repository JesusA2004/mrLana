<?php

namespace App\Http\Requests\Concepto;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConceptoRequest extends FormRequest
{

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'grupo'  => ['required', 'string', 'max:120'],
            'nombre' => ['required', 'string', 'max:150'],
            'activo' => ['nullable', 'boolean'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'grupo'  => is_string($this->grupo) ? trim($this->grupo) : $this->grupo,
            'nombre' => is_string($this->nombre) ? trim($this->nombre) : $this->nombre,
        ]);
    }

}
