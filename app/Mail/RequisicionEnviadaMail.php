<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequisicionEnviadaMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(public Requisicion $requisicion) {}

    public function build(){
        $folio = $this->requisicion->folio ?? $this->requisicion->id;
        return $this->subject("Requisición enviada #{$folio}")
            ->view('emails.requisiciones.enviada', [
                'r' => $this->requisicion,
            ]);
    }

}
