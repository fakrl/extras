<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $jenis,
        public string $pesan,
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        try {
            $terkirim = $whatsapp->kirim($this->user->nomor_wa, $this->pesan);
            NotificationLog::catat($this->user->id, $this->jenis, $terkirim, 'whatsapp');
        } catch (\Throwable $e) {
            NotificationLog::catat($this->user->id, $this->jenis, false, 'whatsapp');
        }
    }
}
