<?php

namespace App\Http\Requests\Corporativo;

use Illuminate\Foundation\Http\FormRequest;

class StoreCorporativoRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nombre'    => ['required','string','max:150'],
            'rfc'       => ['nullable','string','max:20'],
            'direccion' => ['nullable','string','max:255'],
            'telefono'  => ['nullable','string','max:30'],
            'email'     => ['nullable','email','max:150'],
            'codigo'    => ['nullable','string','max:20'],
            'activo'    => ['nullable','boolean'],
            'logo_path' => ['nullable','string','max:255'],
            'logo'      => ['nullable','file','mimes:jpg,jpeg,png,webp','max:2048'],
        ];
    }

    public function prepareForValidation(): void
    {
        $logoPath = $this->input('logo_path');

        if (is_string($logoPath)) {
            $logoPath = trim($logoPath);
            if ($logoPath === '') {
                $logoPath = null;
            } elseif (str_starts_with($logoPath, 'storage/')) {
                $logoPath = '/' . $logoPath; // normaliza a /storage/...
            }
        }

        $this->merge([
            'rfc'       => $this->rfc ? mb_strtoupper(trim((string) $this->rfc)) : null,
            'codigo'    => $this->codigo ? mb_strtoupper(trim((string) $this->codigo)) : null,
            'logo_path' => $logoPath,
            'activo'    => $this->has('activo')
                ? filter_var($this->activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : true,
        ]);
    }
}
