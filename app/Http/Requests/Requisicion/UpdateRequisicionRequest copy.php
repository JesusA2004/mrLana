<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

class RequisicionUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('requisicion')?->id;

        return [
            'folio' => ['required', 'string', 'max:50', "unique:requisicions,folio,{$id}"],
            'tipo' => ['required', 'in:ANTICIPO,REEMBOLSO'],
            'status' => ['required', 'in:BORRADOR,CAPTURADA,PAGADA,POR_COMPROBAR,COMPROBADA,ACEPTADA,RECHAZADA'],

            'solicitante_id' => ['required', 'integer', 'exists:empleados,id'],
            'sucursal_id' => ['required', 'integer', 'exists:sucursals,id'],
            'comprador_corp_id' => ['required', 'integer', 'exists:corporativos,id'],

            'proveedor_id' => ['nullable', 'integer', 'exists:proveedors,id'],
            'concepto_id' => ['required', 'integer', 'exists:conceptos,id'],

            'monto_subtotal' => ['required', 'numeric', 'min:0'],
            'monto_total' => ['required', 'numeric', 'min:0'],

            'fecha_captura' => ['required', 'date'],
            'fecha_pago' => ['nullable', 'date'],

            'observaciones' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
