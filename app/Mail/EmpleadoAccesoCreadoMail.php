<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmpleadoAccesoCreadoMail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $plainPassword
    ) {}

    public function build() {

        $headerPath = public_path('img/header.png');
        $firmaPath  = public_path('img/userSend.png');

        // CIDs “manuales” (strings únicos)
        $cidHeader = 'header-img';
        $cidFirma  = 'firma-img';

        return $this
            ->subject('Acceso creado - MR-Lana ERP')
            ->view('emails.empleados.acceso-creado')
            ->with([
                'user'          => $this->user,
                'plainPassword' => $this->plainPassword,
                'cidHeader'     => "cid:$cidHeader",
                'cidFirma'      => "cid:$cidFirma",
            ])
            ->withSymfonyMessage(function ($message) use ($headerPath, $firmaPath, $cidHeader, $cidFirma) {
                // Adjunta inline (CID)
                if (is_file($headerPath)) {
                    $message->embedFromPath($headerPath, $cidHeader);
                }
                if (is_file($firmaPath)) {
                    $message->embedFromPath($firmaPath, $cidFirma);
                }
            });
    }

}
