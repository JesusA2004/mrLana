<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComprobanteRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'archivo' => ['required', 'file', 'max:12288', 'mimes:pdf,jpg,jpeg,png,webp'],
            'fecha_emision' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'tipo_doc' => ['required', 'in:FACTURA,TICKET,NOTA,OTRO'],
        ];
    }

    public function messages(): array {
        return [
            'archivo.required' => 'Sube el comprobante.',
            'archivo.mimes' => 'El archivo debe ser PDF o imagen (JPG/PNG/WebP).',
            'monto.min' => 'El monto debe ser mayor a 0.',
            'tipo_doc.in' => 'Tipo de comprobante inválido.',
        ];
    }

}
