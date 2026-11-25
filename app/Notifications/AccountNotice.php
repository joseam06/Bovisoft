<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AccountNotice extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $title, public array $lines, public string $action = '', public string $url = '')
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return AccountMail::message($this->title, $this->lines, $this->action, $this->url);
    }
}
