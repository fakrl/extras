<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Bagian CU: 8 bug visual dari screenshot (review Gemini). */
class CuBugVisualTest extends TestCase
{
    use RefreshDatabase;

    private function extras(): User
    {
        $u = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $u->id]);

        return $u;
    }

    private function proyekDenganPeran(array $tagPeran): array
    {
        $p = CastingProject::factory()->create(['status' => 'dibuka', 'client_request_status' => 'disetujui']);
        $kelas = $p->classes()->create(['nama_kelas' => 'Mahasiswa', 'budget_client' => 100000, 'kuota_kelas' => 2]);
        $kelas->categories()->sync($tagPeran);

        return [$p, $kelas];
    }

    // CU.4: lencana "% cocok" hanya tampil jika >= 50, di bawah itu tidak tampil apa pun.
    public function test_persen_cocok_di_bawah_50_tidak_tampil_di_lowongan_terbuka(): void
    {
        $tags = collect(['Dewasa', 'Mahasiswa', 'Naik motor', 'Berhijab'])
            ->map(fn ($n) => ExtrasCategory::create(['nama' => $n])->id)->all();

        $u = $this->extras();
        $u->extrasProfile->categories()->sync([$tags[0]]);
        [$p] = $this->proyekDenganPeran([$tags[0], $tags[1], $tags[2]]);

        // 1 dari 3 tag = 33%, dibulatkan < 50 -> tidak boleh tampil sama sekali.
        $html = $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('% cocok', $html);
        $this->assertStringNotContainsString($p->nama_produksi.'</div>', $html); // sanity: proyek memang dirender
    }

    public function test_persen_cocok_50_ke_atas_tampil(): void
    {
        $tags = collect(['Dewasa', 'Mahasiswa'])
            ->map(fn ($n) => ExtrasCategory::create(['nama' => $n])->id)->all();

        $u = $this->extras();
        $u->extrasProfile->categories()->sync($tags);
        $this->proyekDenganPeran($tags);

        $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()->assertSee('100% cocok');
    }

    // CU.5: donat Chart.js diganti 3 angka + bar tersegmen, tanpa canvas/Chart.js di halaman ini.
    public function test_dashboard_client_tanpa_chart_js_pakai_tiga_angka_klik(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['client_id' => $client->id, 'client_request_status' => 'disetujui']);
        $extras = ExtrasProfile::factory()->create();
        $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'lolos']);
        ClientReview::create(['project_application_id' => $app->id, 'client_id' => $client->id, 'keputusan' => 'approve']);

        $html = $this->actingAs($client)->get(route('client.dashboard'))->assertOk()
            ->assertSee('Perlu dipilih')
            ->assertSee('Dipilih')
            ->assertSee('Ditolak')
            ->assertSee(route('client.reviews.index', ['status' => 'approved']), false)
            ->getContent();

        $this->assertStringNotContainsString('chartKeputusan', $html);
        $this->assertStringNotContainsString('chart.umd.min.js', $html);
    }

    // CU.6: tabel Pilih Kandidat jadi kartu di HP - lihat BrOverflowMobileTest (diperluas di sana).

    // CU.5 (temuan review): ?status=<nilai-tidak-dikenal> dulu 500 karena nilai mentah tetap
    // dikirim ke view lalu diindex ke array literal. Sekarang dinormalisasi jadi null di controller.
    public function test_review_index_status_filter_tidak_dikenal_tidak_500(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        CastingProject::factory()->create(['client_id' => $client->id, 'client_request_status' => 'disetujui']);

        $this->actingAs($client)->get(route('client.reviews.index', ['status' => 'nilai-aneh']))
            ->assertOk()->assertDontSee('menunggu dipilih', false);

        $this->actingAs($client)->get(route('client.reviews.index', ['status' => ['x']]))
            ->assertOk();
    }

    private function tugasHonorBelumTransfer(User $admin, CastingProject $p): ProjectApplication
    {
        $extras = ExtrasProfile::factory()->create();
        $app = ProjectApplication::create([
            'casting_project_id' => $p->id, 'extras_id' => $extras->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
        ]);
        $app->payment()->create(['status' => 'belum_dibayar']);

        return $app;
    }

    // CU.7: dashboard SA mengelompokkan per Admin+proyek+jenis tugas (bukan per kandidat).
    public function test_admin_perlu_diingatkan_dikelompokkan_per_admin_proyek_jenis(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Yoga Pratama']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Series Kampus Biru']);

        $apps = collect(range(1, 4))->map(fn () => $this->tugasHonorBelumTransfer($admin, $p));

        $r = $this->actingAs($sa)->get(route('super-admin.dashboard'));
        $r->assertOk();

        $diingatkan = $r->viewData('diingatkan');
        $this->assertCount(1, $diingatkan, 'harusnya 1 baris dikelompokkan, bukan 4 baris per kandidat');
        $this->assertSame(4, $diingatkan->first()['jumlah']);
        $this->assertSame('Honor belum ditransfer', $diingatkan->first()['tugas']);
        $r->assertSee('Honor belum ditransfer', false);
        $r->assertSee('4 Extras', false);

        // Halaman Pantau Admin TETAP rinci per kandidat (tidak ikut dikelompokkan).
        $rp = $this->actingAs($sa)->get(route('super-admin.pantau-admin.index'));
        $baris = $rp->viewData('pantauAdmin')->first(fn ($x) => $x['admin']->id === $admin->id);
        $this->assertCount(4, $baris['tugas']);
    }

    // CU.7 (temuan review): >5 kandidat status sama di 1 Admin tidak boleh kepotong 5 saat
    // dikelompokkan & dikirim notifikasi - pantauAdmin() dipanggil tanpa limit di controller.
    public function test_admin_perlu_diingatkan_tidak_kepotong_5_saat_lebih_dari_5_kandidat(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif', 'name' => 'Dedi Admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui', 'nama_produksi' => 'Film Lintas Waktu']);

        $apps = collect(range(1, 12))->map(fn () => $this->tugasHonorBelumTransfer($admin, $p));

        $r = $this->actingAs($sa)->get(route('super-admin.dashboard'));
        $r->assertOk();

        $diingatkan = $r->viewData('diingatkan');
        $this->assertCount(1, $diingatkan);
        $this->assertSame(12, $diingatkan->first()['jumlah'], 'hitungan kelompok harus 12, bukan kepotong 5');
        $r->assertSee('12 Extras', false);

        $this->actingAs($sa)->post(route('super-admin.pantau-admin.ingatkan'), [
            'project_application_ids' => $apps->pluck('id')->all(),
        ])->assertSessionHas('status');

        $notif = $admin->notifications()->where('data->jenis', 'pengingat_sa')->latest()->first();
        $this->assertStringContainsString('12 Extras', $notif->data['pesan']);
    }

    public function test_ingatkan_kelompok_satu_notifikasi_satu_log_dan_batas_24_jam_per_kelompok(): void
    {
        $this->travelTo('2026-06-10 09:00:00');
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $apps = collect(range(1, 4))->map(fn () => $this->tugasHonorBelumTransfer($admin, $p));

        $this->actingAs($sa)->post(route('super-admin.pantau-admin.ingatkan'), [
            'project_application_ids' => $apps->pluck('id')->all(),
        ])->assertRedirect();

        $this->assertSame(1, $admin->notifications()->where('data->jenis', 'pengingat_sa')->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'REMIND_ADMIN',
            'subject_type' => CastingProject::class,
            'subject_id' => $p->id,
        ]);
        $this->assertSame(1, ActivityLog::where('action', 'REMIND_ADMIN')->count());

        // dalam 24 jam: ditolak, tidak ada notif baru.
        $this->actingAs($sa)->post(route('super-admin.pantau-admin.ingatkan'), [
            'project_application_ids' => $apps->pluck('id')->all(),
        ])->assertSessionHas('error');
        $this->assertSame(1, $admin->notifications()->where('data->jenis', 'pengingat_sa')->count());

        $r = $this->actingAs($sa)->get(route('super-admin.dashboard'));
        $r->assertSee('Sudah diingatkan', false);

        $this->travelTo('2026-06-11 10:00:00');
        $this->actingAs($sa)->post(route('super-admin.pantau-admin.ingatkan'), [
            'project_application_ids' => $apps->pluck('id')->all(),
        ])->assertSessionHas('status');
        $this->assertSame(2, $admin->notifications()->where('data->jenis', 'pengingat_sa')->count());
    }

    // CU.7 (temuan review): badge "Diingatkan SA" di dashboard Admin juga muncul untuk
    // reminder kelompok ('pg-...', dari kartu dashboard SA) - bukan cuma reminder individual ('pa-...').
    public function test_badge_diingatkan_sa_muncul_untuk_reminder_kelompok(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $apps = collect(range(1, 3))->map(fn () => $this->tugasHonorBelumTransfer($admin, $p));

        $this->actingAs($sa)->post(route('super-admin.pantau-admin.ingatkan'), [
            'project_application_ids' => $apps->pluck('id')->all(),
        ])->assertSessionHas('status');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee('Diingatkan SA');
    }

    // Pengingat per-kandidat (dari halaman Pantau Admin) tetap jalan apa adanya (project_application_id tunggal).
    public function test_ingatkan_satu_kandidat_masih_jalan_seperti_sebelumnya(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_id' => $klien->id, 'client_request_status' => 'disetujui']);
        $app = $this->tugasHonorBelumTransfer($admin, $p);

        $this->actingAs($sa)->post(route('super-admin.pantau-admin.ingatkan'), ['project_application_id' => $app->id])
            ->assertRedirect();

        $this->assertSame(1, $admin->notifications()->where('data->jenis', 'pengingat_sa')->count());
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'REMIND_ADMIN',
            'subject_type' => ProjectApplication::class,
            'subject_id' => $app->id,
        ]);
    }
}
