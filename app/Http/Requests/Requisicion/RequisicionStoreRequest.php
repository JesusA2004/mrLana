<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la creación de una requisición.
 */
class RequisicionStoreRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array
    {
        return [
            'folio' => ['required','string','max:50','unique:requisicions,folio'],
            'tipo'  => ['required','in:ANTICIPO,REEMBOLSO'],
            // Validar que el status inicial pertenezca al catálogo de estados
            'status' => ['required','in:BORRADOR,ELIMINADA,CAPTURADA,PAGO_AUTORIZADO,PAGO_RECHAZADO,PAGADA,POR_COMPROBAR,COMPROBACION_ACEPTADA,COMPROBACION_RECHAZADA'],
            'solicitante_id'   => ['required','integer','exists:empleados,id'],
            'sucursal_id'      => ['required','integer','exists:sucursals,id'],
            'comprador_corp_id'=> ['required','integer','exists:corporativos,id'],
            'proveedor_id'     => ['nullable','integer','exists:proveedors,id'],
            'concepto_id'      => ['required','integer','exists:conceptos,id'],
            'monto_subtotal'   => ['required','numeric','min:0'],
            'monto_total'      => ['required','numeric','min:0'],
            // Fechas: la de solicitud es obligatoria; la de autorización es opcional (la coloca un contador/admin)
            'fecha_solicitud'     => ['required','date'],
            'fecha_autorizacion'  => ['nullable','date'],
            'observaciones'    => ['nullable','string','max:2000'],
            // Los detalles llegarán como array de objetos con campos específicos; se valida en el controlador
            'detalles' => ['required','array','min:1'],
            'detalles.*.sucursal_id'   => ['nullable','integer','exists:sucursals,id'],
            'detalles.*.cantidad'      => ['required','numeric','min:0.01'],
            'detalles.*.descripcion'   => ['required','string','max:255'],
            'detalles.*.precio_unitario' => ['required','numeric','min:0'],
            'detalles.*.subtotal'      => ['required','numeric','min:0'],
            'detalles.*.iva'           => ['required','numeric','min:0'],
            'detalles.*.total'         => ['required','numeric','min:0'],
        ];
    }

}
