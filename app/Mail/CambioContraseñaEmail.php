<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CambioContraseñaEmail extends Mailable {

    use Queueable, SerializesModels;

    public function __construct(
        public string $passwordNueva,
        public $user
    ) {}

    public function build() {
        $headerPath = public_path('img/header.png');
        $firmaPath  = public_path('img/userSend.png');

        // IDs de contenido (CID)
        $cidHeader = 'header-img';
        $cidFirma  = 'firma-img';

        return $this
            ->subject('Tu contraseña fue actualizada - MR-Lana ERP')
            ->view('emails.contrasenas.cambio-contrasena')
            ->with([
                'user'            => $this->user,
                'passwordNueva'   => $this->passwordNueva,
                'cidHeader'       => "cid:$cidHeader",
                'cidFirma'        => "cid:$cidFirma",
            ])
            ->withSymfonyMessage(function ($message) use ($headerPath, $firmaPath, $cidHeader, $cidFirma) {
                if (is_file($headerPath)) {
                    $message->embedFromPath($headerPath, $cidHeader);
                }
                if (is_file($firmaPath)) {
                    $message->embedFromPath($firmaPath, $cidFirma);
                }
            });
    }

}
