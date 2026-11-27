<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class AccountPasswordReset extends ResetPassword implements ShouldQueue
{
    use Queueable;

    public function __construct($token)
    {
        parent::__construct($token);
        $this->afterCommit();
    }

    protected function buildMailMessage($url)
    {
        return AccountMail::message('Recupera tu contraseña', ['Recibimos una solicitud para establecer una nueva contraseña.', 'El enlace vence en '.config('auth.passwords.users.expire', 60).' minutos y solo puede utilizarse una vez. Si no lo solicitaste, ignora este correo.'], 'Establecer nueva contraseña', $url);
    }
}
