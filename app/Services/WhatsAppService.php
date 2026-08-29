<?php

namespace App\Services;

use App\Jobs\SendWhatsAppNotification;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RF-37: satu-satunya titik integrasi ke Node service whatsapp-web.js.
 * Laravel TIDAK PERNAH panggil Http::post() langsung tersebar di model/
 * controller — semua lewat sini, biar gampang diganti kalau arsitektur
 * WA berubah lagi nanti.
 */
class WhatsAppService
{
    public function kirim(string $nomorWa, string $pesan): bool
    {
        try {
            $url = rtrim(config('services.whatsapp.url'), '/').'/send';

            return Http::timeout(10)
                ->withToken(config('services.whatsapp.token'))
                ->post($url, ['nomor' => $nomorWa, 'pesan' => $pesan])
                ->successful();
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService::kirim gagal', ['nomor' => $nomorWa, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Dipanggil dari model/controller trigger notif. Skip + catat gagal
     * kalau nomor_wa belum diisi (in-process, murah). Kirim aktualnya
     * di-dispatch ke queue biar HTTP call ke Node tidak blocking request
     * (terutama bulk approve/reject CD).
     *
     * dispatch() sendiri dibungkus try/catch (bukan cuma di dalam job) —
     * audit 30 Agu 2026 nemu: kalau tabel `jobs` gagal di-insert (DB
     * lock/down), dispatch() throw SEBELUM job sempat jalan, jadi
     * try/catch di WhatsAppService::kirim()/job handle() nggak kepakai.
     * Notifikasi WA harus tetap best-effort di titik manapun bisa gagal,
     * termasuk saat enqueue — jangan sampai gagal kirim WA gagalkan aksi
     * utama pemanggil (approve CD, dsb).
     */
    public function kirimNotifikasi(User $user, string $jenis, string $pesan): void
    {
        if (! $user->nomor_wa) {
            NotificationLog::catat($user->id, $jenis, false, 'whatsapp');

            return;
        }

        try {
            SendWhatsAppNotification::dispatch($user, $jenis, $pesan);
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService::kirimNotifikasi gagal dispatch', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            NotificationLog::catat($user->id, $jenis, false, 'whatsapp');
        }
    }
}
