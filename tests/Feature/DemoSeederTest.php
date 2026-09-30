<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ProjectApplication;
use App\Models\User;
use Database\Seeders\DemoLengkapSeeder;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_demo_sesuai_akun_demo_dan_tanpa_kirim_keluar(): void
    {
        Storage::fake('local');
        Mail::fake();
        Queue::fake();
        Http::fake();

        $this->seed([ExtrasCategorySeeder::class, DemoLengkapSeeder::class]);

        Mail::assertNothingSent();
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();
        Http::assertNothingSent();

        $this->assertEquals(
            ['admin' => 4, 'client' => 5, 'extras' => 24, 'korlap' => 2, 'super_admin' => 2],
            User::withTrashed()->selectRaw('role, count(*) c')->groupBy('role')->orderBy('role')->pluck('c', 'role')->all()
        );
        $this->assertTrue(User::where('email', 'fahrulmukhlisin13@gmail.com')->value('is_protected'));
        $this->assertSoftDeleted('users', ['username' => 'lama_dihapus']);

        $p = fn (string $awalan) => CastingProject::where('nama_produksi', 'like', $awalan.'%')->firstOrFail();

        $this->assertEquals(
            ['deal' => 1, 'dibatalkan' => 1, 'diajukan' => 1, 'diajukan_ke_client' => 2, 'direview_admin' => 1, 'ditolak' => 1, 'kontrak_ditandatangani' => 1, 'lolos' => 1, 'nego_fee' => 2],
            ProjectApplication::where('casting_project_id', $p('Iklan "Minuman')->id)
                ->selectRaw('status_partisipasi, count(*) c')->groupBy('status_partisipasi')->orderBy('status_partisipasi')->pluck('c', 'status_partisipasi')->all()
        );

        $invoice = $p('Film "Rumah')->invoices()->sole();
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists([$invoice->pdf_path, $invoice->ttd_admin_signature_path, $invoice->ttd_client_signature_path]);

        $p3 = $p('Series "Kampus Biru"');
        $this->assertEquals(2, $p3->applications()->whereHas('attendances', fn ($q) => $q->where('status_validasi', 'menunggu')
            ->whereHas('eventShootingDate', fn ($d) => $d->whereDate('tanggal', today())))->count());

        $this->assertSame('menunggu_acc', $p('Video Klip')->client_request_status);
        $this->assertSame(['ghost01'], User::mangkrak()->pluck('username')->all());
        $this->assertSame(['ghost01'], User::akanDihapus()->pluck('username')->all());
        $this->get('/')->assertOk()->assertSee('@dimas_rk')->assertDontSee('@arga_p')
            ->assertSee('Rumah di Ujung Senja')->assertSee('PT Kopi Pagi Nusantara')->assertDontSee('Sinar Rumah Produksi')->assertDontSee('PT Layar Senja Films');

        $dimas = ProjectApplication::with('castingProjectClass.categories', 'extras.categories')
            ->where('casting_project_id', $p('Iklan "Minuman')->id)
            ->whereHas('extras.user', fn ($q) => $q->where('username', 'dimas_rk'))->firstOrFail();
        $this->assertSame(100, $dimas->persenCocok());

        User::withTrashed()->withCount('notifications')->get()
            ->each(fn ($u) => $this->assertTrue($u->notifications_count >= 2 && $u->notifications_count <= 4, "{$u->username}: {$u->notifications_count} notif"));
        $this->assertDatabaseHas('notifications', ['read_at' => null]);

        // BM: data demo pakai skema baru
        $joko = User::where('username', 'joko_s')->firstOrFail()->extrasProfile;
        $this->assertSame([3, 'melanggar'], [$joko->cancel_count, $joko->status]);
        $this->assertSame('gagal', User::where('username', 'nadia_pu')->firstOrFail()->notifications()->where('data->jenis', 'hasil_seleksi')->sole()->data['wa']);
        $this->assertSame(6, User::whereNotNull('honor_nominal')->count());
        $this->assertSame(0, CastingProject::whereNull('client_id')->where('nama_produksi', 'not like', 'Iklan "Bank%')->count());
        $this->assertTrue(ClientReview::whereNotNull('grade_client')->exists());
        $this->assertDatabaseHas('activity_logs', ['action' => 'DISPUTE_PAYMENT']);
    }

    public function test_login_satu_akun_per_role_sampai_dashboard(): void
    {
        Storage::fake('local');
        $this->seed([ExtrasCategorySeeder::class, DemoLengkapSeeder::class]);

        foreach (['fakrul', 'admin_rina', 'korlap_bambang', 'client_andini', 'dimas_rk'] as $username) {
            $user = User::where('username', $username)->firstOrFail();

            $this->post('/login', ['email' => $username, 'password' => 'password'])->assertRedirect($user->dashboardUrl());
            $this->get($user->dashboardUrl())->assertOk();

            $this->post('/logout');
        }
    }

    public function test_guard_production(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(\RuntimeException::class);
        app(DemoLengkapSeeder::class)->setContainer(app())->__invoke();
    }
}
