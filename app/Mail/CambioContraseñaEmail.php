<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CambioContraseñaEmail extends Mailable
{
    use Queueable, SerializesModels;

    // Propiedades para almacenar la nueva contraseña y el usuario
    public $passwordNueva;
    public $user;

    // Constructor para inicializar las propiedades
    public function __construct($passwordNueva, $user)
    {
        $this->passwordNueva = $passwordNueva;
        $this->user = $user;
    }

    // Metodo para construir el correo electrónico
    public function build()
    {
        return $this->subject('Tu contraseña fue actualizada')
            ->view('emails.CambioContraseña')
            ->with([
                'passwordNueva' => $this->passwordNueva,
                'user' => $this->user,
            ]);
    }

}
