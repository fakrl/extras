<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BnMangkrakTerjadwalTest extends TestCase
{
    use RefreshDatabase;

    private function extras(int $umurHari, bool $lengkap = false): User
    {
        $u = User::factory()->create(['role' => 'extras']);
        $u->forceFill(['created_at' => now()->subDays($umurHari)])->save();
        if ($lengkap) {
            ExtrasProfile::create(['user_id' => $u->id, 'foto_profil_path' => 'x.jpg', 'nik' => '3201234567890011']);
        }

        return $u;
    }

    private function jumlahPeringatan(User $u): int
    {
        return $u->notifications()->where('data->jenis', 'peringatan_mangkrak')->count();
    }

    public function test_peringatan_terkirim_sekali_ke_yang_akan_mangkrak(): void
    {
        Mail::fake();
        $akan = $this->extras(23);
        $sudah = $this->extras(40);
        $baru = $this->extras(22);
        $lengkap = $this->extras(25, true);

        $this->artisan('akun:peringatkan-mangkrak')->assertSuccessful();
        $this->artisan('akun:peringatkan-mangkrak')->assertSuccessful();

        $this->assertSame(1, $this->jumlahPeringatan($akan));
        $this->assertSame(1, $this->jumlahPeringatan($sudah));
        $this->assertSame(0, $this->jumlahPeringatan($baru));
        $this->assertSame(0, $this->jumlahPeringatan($lengkap));

        $tanggal = $akan->created_at->copy()->addDays(30)->translatedFormat('d F Y');
        $this->assertSame(
            "Profilmu belum lengkap. Lengkapi sebelum {$tanggal} supaya akunmu nggak dihapus otomatis.",
            $akan->notifications()->first()->data['pesan']
        );
        $this->assertStringContainsString(now()->addDays(7)->translatedFormat('d F Y'), $sudah->notifications()->first()->data['pesan']);
        Mail::assertQueued(Mailable::class, 2);
    }

    public function test_hapus_hanya_mangkrak_yang_sudah_diperingatkan_7_hari(): void
    {
        Mail::fake();
        $siap = $this->extras(40);
        $baruDiperingatkan = $this->extras(40);
        $belumDiperingatkan = $this->extras(40);
        $sudahLengkap = $this->extras(40);

        $this->travel(-7)->days();
        $siap->kabari('x', 'x', jenis: 'peringatan_mangkrak');
        $sudahLengkap->kabari('x', 'x', jenis: 'peringatan_mangkrak');
        $this->travelBack();
        $baruDiperingatkan->kabari('x', 'x', jenis: 'peringatan_mangkrak');
        ExtrasProfile::create(['user_id' => $sudahLengkap->id, 'foto_profil_path' => 'x.jpg', 'nik' => '3201234567890011']);

        $this->artisan('akun:hapus-mangkrak')->assertSuccessful();

        $this->assertNull(User::withTrashed()->find($siap->id));
        $this->assertNotNull(User::find($baruDiperingatkan->id));
        $this->assertNotNull(User::find($belumDiperingatkan->id));
        $this->assertNotNull(User::find($sudahLengkap->id));

        $log = ActivityLog::where('action', 'AUTO_PRUNE_ABANDONED_USERS')->sole();
        $this->assertNull($log->user_id);
        $this->assertSame('system', $log->role);
        $this->assertStringContainsString('Sistem', $log->description);
        $this->assertSame('sistem', $log->properties['oleh']);
        $this->assertSame(1, $log->properties['jumlah_akun_dihapus']);
    }

    public function test_filter_akan_dihapus_di_manajemen_akun(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $diperingatkan = $this->extras(25);
        $diperingatkan->update(['name' => 'Calon Terhapus']);
        $diperingatkan->kabari('x', 'x', jenis: 'peringatan_mangkrak');
        $this->extras(25)->update(['name' => 'Belum Diperingatkan']);

        $res = $this->actingAs($sa)->get(route('super-admin.akun.index', ['akan_dihapus' => 1]))->assertOk();
        $this->assertSame([$diperingatkan->id], $res->viewData('users')->pluck('id')->all());
        $res->assertSee('Akan dihapus')->assertSee('Calon Terhapus')->assertDontSee('Belum Diperingatkan');
    }

    public function test_prune_manual_tetap_jalan_dan_jadwal_terdaftar(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $m = $this->extras(40);
        $this->actingAs($admin)->post(route('admin.users.prune'))->assertRedirect();
        $this->assertNull(User::withTrashed()->find($m->id));

        $events = collect(app(Schedule::class)->events())->keyBy(fn ($e) => trim(str($e->command)->after('artisan')->replace(["'", '"'], '')));
        foreach (['akun:peringatkan-mangkrak', 'akun:hapus-mangkrak'] as $cmd) {
            $this->assertTrue($events->has($cmd), $cmd);
            $this->assertSame('Asia/Jakarta', $events[$cmd]->timezone);
            $this->assertMatchesRegularExpression('/^\d+ [5-9] \* \* \*$/', $events[$cmd]->expression);
        }
    }

    public function test_kebijakan_privasi_menyebut_hapus_otomatis(): void
    {
        $this->get(route('privacy-policy'))->assertOk()->assertSee('dihapus otomatis');
    }
}
