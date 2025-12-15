<?php

namespace App\Http\Requests\Area;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAreaRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'corporativo_id' => ['nullable','exists:corporativos,id'],
            'nombre' => ['required','string','max:150'],
            'activo' => ['boolean'],
        ];
    }
}
