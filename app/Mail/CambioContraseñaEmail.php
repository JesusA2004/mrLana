<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CambioContraseñaEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $passwordNueva;
    public $user;

    public function __construct($passwordNueva, $user)
    {
        $this->passwordNueva = $passwordNueva;
        $this->user = $user;
    }

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
