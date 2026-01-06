<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;

class ProveedorIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],
            'estatus' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'string', 'in:nombre_comercial,estatus,created_at'],
            'dir' => ['nullable', 'string', 'in:asc,desc'],
            'perPage' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
