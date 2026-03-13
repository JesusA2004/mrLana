<?php

namespace App\Mail;

use App\Models\Ajuste;
use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisicionAjusteSolicitadoMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public Ajuste $ajuste,
        public Requisicion $requisicion,
        public string $senderName = 'Sistema',
    ) {}

    public function build() {
        return $this
            ->subject('Nuevo ajuste solicitado - Requisición ' . ($this->requisicion->folio ?? $this->requisicion->id))
            ->view('emails.requisiciones.ajuste-solicitado');
    }

}
