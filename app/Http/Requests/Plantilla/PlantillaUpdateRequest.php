<?php

namespace App\Http\Requests\Plantilla;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida la edición de una plantilla. Permite actualizar cabecera y detalles.
 */
class PlantillaUpdateRequest extends FormRequest {

    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        $id = $this->route('plantilla')?->id;

        return [
            'nombre' => ['required','string','max:100'],
            'solicitante_id'    => ['nullable','integer','exists:empleados,id'],
            'sucursal_id'       => ['nullable','integer','exists:sucursals,id'],
            'comprador_corp_id' => ['nullable','integer','exists:corporativos,id'],
            'proveedor_id'      => ['nullable','integer','exists:proveedors,id'],
            'concepto_id'       => ['nullable','integer','exists:conceptos,id'],
            'monto_subtotal'    => ['required','numeric','min:0'],
            'monto_total'       => ['required','numeric','min:0'],
            'fecha_solicitud'   => ['nullable','date'],
            'fecha_autorizacion'=> ['nullable','date'],
            'observaciones'     => ['nullable','string','max:2000'],
            'detalles'          => ['required','array','min:1'],
            'detalles.*.id'     => ['nullable','integer'],
            'detalles.*.sucursal_id' => ['nullable','integer','exists:sucursals,id'],
            'detalles.*.cantidad'    => ['required','numeric','min:0.01'],
            'detalles.*.descripcion' => ['required','string','max:255'],
            'detalles.*.precio_unitario' => ['required','numeric','min:0'],
            'detalles.*.subtotal'    => ['required','numeric','min:0'],
            'detalles.*.iva'         => ['required','numeric','min:0'],
            'detalles.*.total'       => ['required','numeric','min:0'],
        ];
    }

}
