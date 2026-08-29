<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

/**
 * Regresi audit full-codebase (30 Agu 2026): SendWhatsAppNotification::
 * dispatch() bisa throw SENDIRI (bukan cuma job-nya) kalau insert ke
 * tabel `jobs` gagal (DB lock/down) — try/catch lama cuma ada di dalam
 * WhatsAppService::kirim()/job handle(), yang keduanya baru jalan
 * SETELAH dispatch() sukses. kirimNotifikasi() harus tetap best-effort
 * walau dispatch() sendiri yang gagal.
 */
class WhatsAppDispatchFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_kirim_notifikasi_tidak_lempar_exception_kalau_dispatch_gagal(): void
    {
        $user = User::factory()->create(['role' => 'extras', 'nomor_wa' => '628123456789']);

        $this->app->bind(Dispatcher::class, function () {
            $mock = Mockery::mock(Dispatcher::class);
            $mock->shouldReceive('dispatch')->andThrow(new \RuntimeException('jobs table down'));

            return $mock;
        });

        app(WhatsAppService::class)->kirimNotifikasi($user, 'hasil_seleksi', 'pesan test');

        $this->assertDatabaseHas('notifications_log', [
            'user_id' => $user->id,
            'jenis' => 'hasil_seleksi',
            'channel' => 'whatsapp',
            'status' => 'gagal',
        ]);
    }
}
