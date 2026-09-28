<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InAppNotification extends Notification
{
    public function __construct(public string $judul, public string $pesan, public ?string $url = null) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return ['judul' => $this->judul, 'pesan' => $this->pesan, 'url' => $this->url];
    }
}
