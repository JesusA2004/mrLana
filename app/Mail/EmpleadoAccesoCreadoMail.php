<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmpleadoAccesoCreadoMail extends Mailable{

    // Traits
    use Queueable, SerializesModels;

    // Constructor
    public function __construct(
        public User $user,
        public string $plainPassword
    ) {}

    // Metodo para construir el correo
    public function build()
    {
        return $this
            ->subject('Acceso creado - MR-Lana ERP')
            ->view('emails.empleados.acceso-creado')
            ->with([
                'user' => $this->user,
                'plainPassword' => $this->plainPassword,
            ]);
    }
    
}
