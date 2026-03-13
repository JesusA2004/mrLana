<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisicionComprobadaMail extends Mailable {

    use Queueable, SerializesModels;

    public Requisicion $requisicion;

    public function __construct(Requisicion $requisicion) {
        $this->requisicion = $requisicion;
    }

    public function build(): self {
        return $this
            ->subject('Tu requisición fue comprobada correctamente')
            ->view('emails.requisiciones.comprobada');
    }

}
