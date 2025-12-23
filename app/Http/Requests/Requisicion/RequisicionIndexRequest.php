<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

class RequisicionIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:200'],

            'tab' => ['nullable', 'string', 'max:20'], // PENDIENTES/APROBADAS/RECHAZADAS/TODAS

            'status' => ['nullable', 'string', 'max:30'],
            'tipo' => ['nullable', 'in:ANTICIPO,REEMBOLSO'],

            'comprador_corp_id' => ['nullable', 'integer'],
            'sucursal_id' => ['nullable', 'integer'],
            'solicitante_id' => ['nullable', 'integer'],

            'fecha_from' => ['nullable', 'date'],
            'fecha_to' => ['nullable', 'date'],

            'perPage' => ['nullable', 'integer', 'min:10', 'max:100'],

            'sort' => ['nullable', 'in:id,folio,tipo,status,monto_total,fecha_captura,fecha_pago'],
            'dir' => ['nullable', 'in:asc,desc'],
        ];
    }
}
