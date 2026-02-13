<?php

namespace App\Mail;

use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;

class RequisicionComprobacionesNotifyMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public Requisicion $requisicion,
        public string $messageText,
        public string $senderName = 'Sistema',
    ) {}

    public function envelope(): Envelope {
        $folio = $this->requisicion->folio ?? $this->requisicion->id;
        return new Envelope(
            subject: "Comprobaciones listas para revisión · Folio {$folio}"
        );
    }

    public function content(): Content {
        return new Content(
            view: 'emails.requisiciones.comprobaciones_notify',
            with: [
                'req' => $this->requisicion,
                'messageText' => $this->messageText,
                'senderName' => $this->senderName,
                'erpUrl' => config('erp.erp_url'),
                'supportUrl' => config('erp.support_url'),
            ]
        );
    }

}
