<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class BulkDestroyAreaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:areas,id'],
        ];
    }
}
