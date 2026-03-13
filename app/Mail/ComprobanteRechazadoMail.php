<?php

namespace App\Mail;

use App\Models\Comprobante;
use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ComprobanteRechazadoMail extends Mailable {

    use Queueable, SerializesModels;

    public Requisicion $requisicion;
    public Comprobante $comprobante;

    public function __construct(Requisicion $requisicion, Comprobante $comprobante) {
        $this->requisicion = $requisicion;
        $this->comprobante = $comprobante;
    }

    public function build(): self {
        return $this
            ->subject('Uno de tus comprobantes fue rechazado')
            ->view('emails.requisiciones.comprobante-rechazado');
    }

}
