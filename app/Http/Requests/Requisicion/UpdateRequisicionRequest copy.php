<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * UpdateRequisicionRequest
 *
 */
class UpdateRequisicionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('requisicion')?->id;

        return [
            'corporativo_id' => ['nullable', 'integer'],
            'sucursal_id'    => ['nullable', 'integer'],
            'solicitante_id' => ['nullable', 'integer'],
            'proveedor_id'   => ['nullable', 'integer'],
            'concepto_id'    => ['nullable', 'integer'],

            'folio_unico'    => ['required', 'string', 'max:60', 'unique:requisicions,folio_unico,' . $id],
            'tipo'           => ['required', 'string', 'max:30'],
            'descripcion'    => ['nullable', 'string', 'max:500'],
            'monto_total'    => ['required', 'numeric', 'min:0'],
            'moneda'         => ['required', 'string', 'max:10'],
            'status'         => ['required', 'string', 'max:30'],
            'fecha_captura'  => ['required', 'date'],
        ];
    }
}
