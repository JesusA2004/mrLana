<?php

namespace App\Http\Requests\Proveedor;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct', 'min:1'],
        ];
    }
}
