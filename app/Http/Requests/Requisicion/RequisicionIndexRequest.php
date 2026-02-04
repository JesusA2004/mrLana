<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida los parámetros de consulta del listado de requisiciones.
 */
class RequisicionIndexRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return [
            'q' => ['nullable','string','max:200'],
            'tab' => ['nullable','string','max:20'], // PENDIENTES/AUTORIZADAS/RECHAZADAS/TODAS
            'status' => ['nullable','string','max:30'],
            'tipo'   => ['nullable','in:ANTICIPO,REEMBOLSO'],
            'comprador_corp_id' => ['nullable','integer'],
            'sucursal_id'       => ['nullable','integer'],
            'solicitante_id'    => ['nullable','integer'],
            // Fechas de solicitud a filtrar (from/to)
            'fecha_from' => ['nullable','date'],
            'fecha_to'   => ['nullable','date'],
            'perPage' => ['nullable','integer','min:10','max:100'],
            // Campos de ordenamiento actualizados: se incluyen las nuevas fechas
            'sort' => ['nullable','in:id,folio,tipo,status,monto_total,fecha_solicitud,fecha_autorizacion'],
            'dir'  => ['nullable','in:asc,desc'],
        ];
    }

}
