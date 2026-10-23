<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * BK.1: URL notifikasi disimpan & dirender tanpa domain (nggak nyasar ke ngrok/APP_URL lain).
 */
class BkNotifUrlRelatifTest extends TestCase
{
    use RefreshDatabase;

    public function test_url_notif_disimpan_relatif(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        $user->notify(new InAppNotification('Nego', 'Ada tawaran baru', 'https://x.test/extras/nego/1?a=b'));

        $this->assertSame('/extras/nego/1?a=b', $user->notifications()->first()->data['url']);
        $this->assertNull(InAppNotification::relatif(null));
        $this->assertSame('/', InAppNotification::relatif('https://x.test'));
    }

    public function test_notif_lama_absolut_dirender_relatif(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $notif = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => InAppNotification::class,
            'data' => ['judul' => 'Lama', 'pesan' => 'x', 'url' => 'https://ferris-hardy-judo.ngrok-free.dev/admin/dashboard'],
        ]);

        // item notif lewat route baca (tandai dibaca), lalu dialihkan ke URL relatif
        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('notifications.baca', $notif->id), false)
            ->assertDontSee('href="https://ferris-hardy-judo.ngrok-free.dev/admin/dashboard"  style', false);
        $this->get(route('notifications.baca', $notif->id))->assertRedirect('/admin/dashboard');
        $this->assertNotNull($notif->fresh()->read_at);
    }
}
