<?php

namespace App\Services;

use App\Jobs\SendWhatsAppNotification;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RF-37: satu-satunya titik integrasi ke Node service whatsapp-web.js.
 * Laravel TIDAK PERNAH panggil Http::post() langsung tersebar di model/
 * controller, semua lewat sini, biar gampang diganti kalau arsitektur
 * WA berubah lagi nanti.
 */
class WhatsAppService
{
    public function kirim(string $nomorWa, string $pesan): bool
    {
        try {
            return $this->kirimLangsung($nomorWa, $pesan)->successful();
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService::kirim gagal', ['nomor' => $nomorWa, 'error' => $e->getMessage()]);

            return false;
        }
    }

    // BO.1: nomor dinormalisasi ke 62xxx (format whatsapp-web.js). Throw kalau nomor ngaco / Node mati.
    public function kirimLangsung(string $nomorWa, string $pesan): Response
    {
        $nomor = User::normalisasiWa($nomorWa) ?? throw new \InvalidArgumentException("Nomor WA tidak valid: {$nomorWa}");

        return Http::timeout(10)
            ->withToken((string) config('services.whatsapp.token'))
            ->post(rtrim(config('services.whatsapp.url'), '/').'/send', ['nomor' => $nomor, 'pesan' => $pesan]);
    }

    /**
     * Dipanggil dari User::kabari(). Nomor kosong -> langsung `gagal`. Kirim aktual
     * di-queue biar HTTP ke Node tidak blocking request. dispatch() ikut dibungkus
     * try/catch: insert ke tabel `jobs` bisa gagal sendiri, dan gagal kirim WA
     * tidak boleh menggagalkan aksi utama pemanggil.
     */
    public function kirimNotifikasi(User $user, string $jenis, string $pesan, ?string $notifikasiId = null): void
    {
        if (! $user->nomor_wa) {
            self::catatStatus($notifikasiId, false);

            return;
        }

        try {
            SendWhatsAppNotification::dispatch($user, $jenis, $pesan, $notifikasiId);
        } catch (\Throwable $e) {
            Log::warning('WhatsAppService::kirimNotifikasi gagal dispatch', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            self::catatStatus($notifikasiId, false);
        }
    }

    /** BM.1: status WA disimpan di data notifikasi in-app pasangannya. */
    public static function catatStatus(?string $notifikasiId, bool $terkirim): void
    {
        $notif = $notifikasiId ? DatabaseNotification::find($notifikasiId) : null;
        $notif?->forceFill(['data' => [...$notif->data, 'wa' => $terkirim ? 'terkirim' : 'gagal', 'wa_dikirim_at' => now()->toDateTimeString()]])->save();
    }
}
