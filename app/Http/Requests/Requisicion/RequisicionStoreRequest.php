<?php

namespace App\Http\Requests\Requisicion;

use Illuminate\Foundation\Http\FormRequest;

class RequisicionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'accion'            => ['required', 'string', 'in:BORRADOR,ENVIAR'],

            'solicitante_id'    => ['required', 'integer', 'exists:empleados,id'],

            'comprador_corp_id' => ['required', 'integer', 'exists:corporativos,id'],
            'sucursal_id'       => ['required', 'integer', 'exists:sucursals,id'],
            'concepto_id'       => ['required', 'integer', 'exists:conceptos,id'],
            'proveedor_id'      => ['nullable', 'integer', 'exists:proveedors,id'],

            'fecha_solicitud'   => ['required', 'date_format:Y-m-d'],
            'fecha_autorizacion'=> ['nullable', 'date_format:Y-m-d'],

            'observaciones'     => ['nullable', 'string', 'max:5000'],

            'detalles'                     => ['required', 'array', 'min:1'],
            'detalles.*.sucursal_id'       => ['nullable', 'integer', 'exists:sucursals,id'],
            'detalles.*.cantidad'          => ['required', 'numeric', 'gt:0'],
            'detalles.*.descripcion'       => ['required', 'string', 'min:2', 'max:255'],
            'detalles.*.precio_unitario'   => ['required', 'numeric', 'gte:0'],
            'detalles.*.genera_iva'        => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'accion.required' => 'Define si vas a guardar como borrador o enviar.',
            'accion.in'       => 'Acción inválida.',

            'solicitante_id.required' => 'El solicitante es obligatorio.',
            'solicitante_id.exists'   => 'El solicitante seleccionado no existe.',

            'comprador_corp_id.required' => 'El comprador (corporativo) es obligatorio.',
            'comprador_corp_id.exists'   => 'El comprador seleccionado no existe.',

            'sucursal_id.required' => 'La sucursal es obligatoria.',
            'sucursal_id.exists'   => 'La sucursal seleccionada no existe.',

            'concepto_id.required' => 'El concepto es obligatorio.',
            'concepto_id.exists'   => 'El concepto seleccionado no existe.',

            'proveedor_id.exists'  => 'El proveedor seleccionado no existe.',

            'fecha_solicitud.required'    => 'La fecha de solicitud es obligatoria.',
            'fecha_solicitud.date_format' => 'La fecha de solicitud debe tener formato YYYY-MM-DD.',

            // IMPORTANTE: opcional
            'fecha_autorizacion.date_format' => 'La fecha de autorización debe tener formato YYYY-MM-DD.',

            'detalles.required' => 'Agrega al menos un item.',
            'detalles.min'      => 'Agrega al menos un item.',

            'detalles.*.cantidad.required' => 'La cantidad es obligatoria en cada item.',
            'detalles.*.cantidad.gt'       => 'La cantidad debe ser mayor a 0.',
            'detalles.*.descripcion.required' => 'La descripción es obligatoria en cada item.',
            'detalles.*.descripcion.min'      => 'La descripción debe tener al menos 2 caracteres.',
            'detalles.*.precio_unitario.required' => 'El precio unitario es obligatorio en cada item.',
            'detalles.*.precio_unitario.gte'      => 'El precio unitario no puede ser negativo.',
            'detalles.*.genera_iva.required'      => 'Define si el item genera IVA.',
            'detalles.*.genera_iva.boolean'       => 'El campo "genera IVA" es inválido.',
        ];
    }
}
