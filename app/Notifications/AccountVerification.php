<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class AccountVerification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    protected function buildMailMessage($url)
    {
        return AccountMail::message('Verifica tu correo', ['Confirma que este correo es tuyo para proteger tu cuenta y recuperar el acceso a tus registros.', 'El enlace vence en 60 minutos. Si ya lo utilizaste, no necesitas repetir la verificación.'], 'Verificar mi correo', $url);
    }
}
