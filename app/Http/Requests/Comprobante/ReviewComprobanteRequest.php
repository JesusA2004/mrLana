<?php

namespace App\Http\Requests\Comprobante;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewComprobanteRequest extends FormRequest {

    public function authorize(): bool
    {
        $role = strtoupper((string) ($this->user()?->role ?? ''));
        return in_array($role, ['ADMIN', 'CONTA', 'CONTABILIDAD'], true);
    }

    public function rules(): array {
        return [
            'estatus' => ['required', Rule::in(['APROBADO','RECHAZADO'])],
            'comentario_revision' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator) {
        $validator->after(function ($v) {
            $estatus = (string) $this->input('estatus');
            $coment = trim((string) $this->input('comentario_revision'));

            if ($estatus === 'RECHAZADO' && $coment === '') {
                $v->errors()->add('comentario_revision', 'Indica el motivo del rechazo.');
            }
        });
    }

}
