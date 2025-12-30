<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Symfony\Component\Mime\Email;

class ResetPasswordNotification extends BaseResetPassword {

    public function toMail($notifiable): MailMessage {

        $url = $this->resetUrl($notifiable);

        $expireMinutes = (int) config(
            'auth.passwords.' . config('auth.defaults.passwords') . '.expire',
            60
        );

        $headerPath = public_path('img/header.png');
        $firmaPath  = public_path('img/userSend.png');

        // Content-IDs (CID)
        $cidHeader = 'header-img';
        $cidFirma  = 'firma-img';

        return (new MailMessage)
            ->subject('Restablecer contraseña - MR-Lana ERP')
            ->view('emails.contrasenas.recuperarcontrasena', [
                'user' => $notifiable,
                'url' => $url,
                'expireMinutes' => $expireMinutes,

                // Importante: el blade usará esto como src=""
                'cidHeader' => "cid:$cidHeader",
                'cidFirma'  => "cid:$cidFirma",
            ])
            ->withSymfonyMessage(function (Email $message) use ($headerPath, $firmaPath, $cidHeader, $cidFirma) {
                // Embed inline (CID)
                if (is_file($headerPath)) {
                    $message->embedFromPath($headerPath, $cidHeader);
                }
                if (is_file($firmaPath)) {
                    $message->embedFromPath($firmaPath, $cidFirma);
                }
            });
    }

    protected function resetUrl($notifiable): string {

        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

}
