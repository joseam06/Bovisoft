<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class AccountMail
{
    public static function message(string $title, array $lines, string $action = '', string $url = ''): MailMessage
    {
        return (new MailMessage)->subject('BoviSoft · '.$title)->view('emails.account', compact('title', 'lines', 'action', 'url'));
    }
}
