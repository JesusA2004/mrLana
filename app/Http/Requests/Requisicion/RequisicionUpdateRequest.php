<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la edición de una requisición existente.
 * Se permite editar folio y fechas pero respeta el identificador para la regla de unicidad.
 */
class RequisicionUpdateRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        $id = $this->route('requisicion')?->id;

        return [
            'folio' => ['required','string','max:50',"unique:requisicions,folio,{$id}"],
            'tipo'  => ['required','in:ANTICIPO,REEMBOLSO'],
            'status' => ['required','in:BORRADOR,ELIMINADA,CAPTURADA,PAGO_AUTORIZADO,PAGO_RECHAZADO,PAGADA,POR_COMPROBAR,COMPROBACION_ACEPTADA,COMPROBACION_RECHAZADA'],
            'solicitante_id'    => ['required','integer','exists:empleados,id'],
            'sucursal_id'       => ['required','integer','exists:sucursals,id'],
            'comprador_corp_id' => ['required','integer','exists:corporativos,id'],
            'proveedor_id'      => ['nullable','integer','exists:proveedors,id'],
            'concepto_id'       => ['required','integer','exists:conceptos,id'],
            'monto_subtotal'    => ['required','numeric','min:0'],
            'monto_total'       => ['required','numeric','min:0'],
            'fecha_solicitud'    => ['required','date'],
            'fecha_autorizacion' => ['nullable','date'],
            'observaciones'     => ['nullable','string','max:2000'],
            // Reglas opcionales si se actualizan detalles desde la UI.
            'detalles' => ['nullable','array'],
            'detalles.*.id'         => ['nullable','integer'],
            'detalles.*.sucursal_id'=> ['nullable','integer','exists:sucursals,id'],
            'detalles.*.cantidad'   => ['required_with:detalles.*.descripcion','numeric','min:0.01'],
            'detalles.*.descripcion'=> ['required_with:detalles.*.cantidad','string','max:255'],
            'detalles.*.precio_unitario' => ['required_with:detalles.*.cantidad','numeric','min:0'],
            'detalles.*.subtotal'   => ['required_with:detalles.*.cantidad','numeric','min:0'],
            'detalles.*.iva'        => ['required_with:detalles.*.cantidad','numeric','min:0'],
            'detalles.*.total'      => ['required_with:detalles.*.cantidad','numeric','min:0'],
        ];
    }

}
