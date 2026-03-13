<?php

namespace App\Mail;

use App\Models\Ajuste;
use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisicionAjusteAplicadoMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public Ajuste $ajuste,
        public Requisicion $requisicion,
    ) {}

    public function build() {
        return $this
            ->subject('Tu ajuste fue aplicado - Requisición ' . ($this->requisicion->folio ?? $this->requisicion->id))
            ->view('emails.requisiciones.ajuste-aplicado');
    }

}
