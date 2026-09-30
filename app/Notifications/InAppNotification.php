<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InAppNotification extends Notification
{
    public function __construct(public string $judul, public string $pesan, public ?string $url = null, public array $data = [])
    {
        $this->url = self::relatif($url);
    }

    /**
     * Simpan URL tanpa domain (/extras/nego/11), bukan https://domain/... .
     * route() ikut host request/APP_URL (mis. ngrok), jadi kalau disimpan
     * absolut, notif yang dibuat lewat ngrok bakal nyasar ke ngrok walau
     * dibuka dari localhost (dan sebaliknya).
     */
    public static function relatif(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $bagian = parse_url($url);
        $path = ($bagian['path'] ?? '/').(isset($bagian['query']) ? '?'.$bagian['query'] : '');

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return ['judul' => $this->judul, 'pesan' => $this->pesan, 'url' => $this->url, ...$this->data];
    }
}
