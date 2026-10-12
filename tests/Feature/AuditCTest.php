<?php

namespace Tests\Feature;

use App\Exports\ClientRiwayatExport;
use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use Database\Seeders\DemoLengkapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Audit 12 Okt 2026, batch C: label, konfirmasi, konteks, mode lihat. */
class AuditCTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'aktif'], $attrs));
    }

    private function proyek(?User $admin = null, array $attrs = []): CastingProject
    {
        return CastingProject::create(array_merge([
            'admin_id' => ($admin ?? $this->user('admin'))->id,
            'nama_produksi' => 'Proyek '.fake()->unique()->lexify('????'),
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ], $attrs));
    }

    private function aplikasi(CastingProject $project, string $status): ProjectApplication
    {
        $u = $this->user('extras');
        $extras = ExtrasProfile::create(['user_id' => $u->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
            'fee_final' => 200000,
        ]);
    }

    // EX-11 + EX-10

    public function test_extras_bisa_membatalkan_keikutsertaan_dari_dashboard(): void
    {
        $p = $this->proyek();
        $p->shootingDates()->create(['tanggal' => today()->addDays(10)]);
        $app = $this->aplikasi($p, 'lolos');

        $res = $this->actingAs($app->extras->user)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Batalkan keikutsertaan')
            ->assertDontSee('dihitung batal mendadak');
        $res->assertSee(route('extras.negotiations.batalkan', $app), false);

        $this->actingAs($app->extras->user)->post(route('extras.negotiations.batalkan', $app), ['alasan' => 'Sakit'])->assertSessionHas('status');
        $this->assertSame('dibatalkan', $app->fresh()->status_partisipasi);
        $this->assertFalse((bool) $app->cancellations()->first()->is_mendadak);
    }

    public function test_dialog_batal_memperingatkan_batal_mendadak_dan_hitungannya(): void
    {
        $p = $this->proyek();
        $p->shootingDates()->create(['tanggal' => today()->addDay()]);
        $app = $this->aplikasi($p, 'kontrak_ditandatangani');

        $this->actingAs($app->extras->user)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('dihitung batal mendadak')
            ->assertSee('0 dari 3');
    }

    public function test_pesan_login_akun_melanggar_menjelaskan_aturan(): void
    {
        $u = $this->user('extras', ['email' => 'langgar@jbtb.test', 'password' => 'rahasia123']);
        ExtrasProfile::create(['user_id' => $u->id])->forceFill(['status' => 'melanggar'])->save();

        $this->post('/login', ['email' => 'langgar@jbtb.test', 'password' => 'rahasia123'])
            ->assertSessionHasErrors(['email' => 'Akun Anda ditandai melanggar karena 3 kali batal mendadak (kurang dari 2 hari sebelum syuting). Hubungi admin JBTB bila ingin meminta peninjauan.']);
    }

    // EX-26

    public function test_klik_notifikasi_menandai_dibaca_lalu_pindah(): void
    {
        $u = $this->user('extras');
        $u->kabari('Judul', 'Pesan', '/extras/dashboard');
        $notif = $u->unreadNotifications()->first();

        $this->actingAs($u)->get(route('notifications.baca', $notif->id))->assertRedirect('/extras/dashboard');
        $this->assertNotNull($notif->fresh()->read_at);
        $this->actingAs($this->user('extras'))->get(route('notifications.baca', $notif->id))->assertNotFound();
    }

    // EX-29 + AD-22

    public function test_nominal_nego_nol_ditolak(): void
    {
        $app = $this->aplikasi($this->proyek(), 'nego_fee');
        $app->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 300000, 'aksi' => 'tawar']);

        $this->actingAs($app->extras->user)->post(route('extras.negotiations.counter', $app), ['nominal' => 0])->assertSessionHasErrors('nominal');
        $this->actingAs($app->extras->user)->get(route('extras.negotiations.show', $app))->assertOk()->assertSee('Ajukan Tawar Balik')->assertSee('min="1"', false);
    }

    // AD-30

    public function test_deadline_tidak_boleh_lampau_atau_lewat_shooting_pertama(): void
    {
        $admin = $this->user('admin');
        $client = $this->user('client');
        $dasar = [
            'nama_produksi' => 'Film Uji', 'client_id' => $client->id, 'kuota' => 3,
            'tanggal_shooting' => [today()->addDays(10)->toDateString()],
            'kelas' => [['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 3]],
        ];

        $this->actingAs($admin)->post(route('admin.projects.store'), $dasar + ['deadline' => today()->subDay()->toDateString()])->assertSessionHasErrors('deadline');
        $this->actingAs($admin)->post(route('admin.projects.store'), $dasar + ['deadline' => today()->addDays(11)->toDateString()])->assertSessionHasErrors('deadline');
        $this->assertSame(0, CastingProject::count());

        $res = $this->actingAs($admin)->post(route('admin.projects.store'), $dasar + ['deadline' => today()->addDays(10)->toDateString()]);
        $proyek = CastingProject::first();
        $res->assertRedirect(route('admin.projects.show', $proyek));
    }

    public function test_form_buat_proyek_punya_batal_dan_istilah_peran(): void
    {
        $this->actingAs($this->user('admin'))->get(route('admin.projects.create'))->assertOk()
            ->assertSee('Batal')->assertSee('Peran yang Dibutuhkan')->assertDontSee('Karakter yang Dibutuhkan');
    }

    // AD-09

    public function test_dashboard_admin_default_proyek_sendiri_dengan_toggle_semua(): void
    {
        $saya = $this->user('admin');
        $lain = $this->user('admin');
        $this->proyek($saya, ['status' => 'dibuka']);
        $this->proyek($lain, ['status' => 'dibuka']);
        $this->proyek($lain, ['status' => 'dibuka']);

        $this->actingAs($saya)->get(route('admin.dashboard'))->assertOk()
            ->assertViewHas('proyekAktif', 1)->assertSee('Proyek saya');
        $this->actingAs($saya)->get(route('admin.dashboard', ['semua' => 1]))->assertOk()->assertViewHas('proyekAktif', 3);
    }

    // AD-06

    public function test_dashboard_menautkan_langsung_ke_sengketa(): void
    {
        $admin = $this->user('admin');
        $app = $this->aplikasi($this->proyek($admin), 'kontrak_ditandatangani');
        Payment::create(['project_application_id' => $app->id, 'status' => 'disengketakan', 'alasan_sengketa' => 'Belum masuk']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee(route('payments.show', $app), false);
    }

    // AD-08

    public function test_tanpa_pic_client_tidak_menghitung_pengajuan_belum_acc(): void
    {
        $admin = $this->user('admin');
        $this->proyek($admin, ['client_id' => null, 'client_request_status' => 'menunggu_acc']);
        $yatim = $this->proyek($admin, ['client_id' => null]);

        $this->assertSame([$yatim->id], CastingProject::tanpaPicClient()->pluck('id')->all());
    }

    // SA-09

    public function test_tambah_hapus_dan_pulihkan_akun_staf_tercatat(): void
    {
        $sa = $this->user('super_admin');

        $this->actingAs($sa)->post(route('super-admin.admins.store'), ['name' => 'Staf Baru', 'email' => 'staf@jbtb.test', 'password' => 'rahasia123', 'role' => 'korlap'])
            ->assertRedirect();
        $staf = User::where('email', 'staf@jbtb.test')->firstOrFail();
        $this->assertDatabaseHas('activity_logs', ['action' => 'CREATE_USER', 'subject_id' => $staf->id]);

        $this->actingAs($sa)->delete(route('super-admin.admins.destroy', $staf));
        $this->assertDatabaseHas('activity_logs', ['action' => 'DELETE_USER', 'subject_id' => $staf->id]);

        $this->actingAs($sa)->patch(route('super-admin.admins.restore', $staf->id));
        $this->assertDatabaseHas('activity_logs', ['action' => 'RESTORE_USER', 'subject_id' => $staf->id]);
    }

    public function test_dialog_tambah_staf_terbuka_lagi_setelah_error(): void
    {
        $sa = $this->user('super_admin');

        $this->actingAs($sa)->from(route('super-admin.akun.index'))->post(route('super-admin.admins.store'), ['_dialog' => 'add-admin', 'name' => '', 'email' => 'x', 'password' => '1', 'role' => 'admin']);
        $this->actingAs($sa)->get(route('super-admin.akun.index'))->assertOk()
            ->assertSee("document.getElementById('add-admin-dialog').showModal()", false);
    }

    public function test_tombol_terapkan_bulk_nonaktif_sampai_aksi_dipilih(): void
    {
        $this->actingAs($this->user('super_admin'))->get(route('super-admin.akun.index'))->assertOk()
            ->assertSee('id="bulk-terapkan"', false)->assertSee('disabled', false);
    }

    // SA-12 + SA-13

    public function test_membuka_halaman_super_admin_keluar_dari_mode(): void
    {
        $sa = $this->user('super_admin');
        $client = $this->user('client');

        $this->actingAs($sa)->withSession(['sa_mode' => 'client', 'sa_view_user_id' => $client->id])
            ->get(route('super-admin.akun.index'))->assertOk()->assertSessionMissing('sa_mode')->assertDontSee('Mode lihat saja');
        $this->assertDatabaseHas('activity_logs', ['action' => 'SA_MODE_KELUAR']);

        $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])
            ->get(route('super-admin.activity-logs'))->assertOk()->assertSessionMissing('sa_mode');
    }

    public function test_mode_admin_tetap_untuk_halaman_admin_dan_pencarian(): void
    {
        $sa = $this->user('super_admin');

        $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])->get(route('admin.dashboard'))->assertOk()->assertSessionHas('sa_mode', 'admin');
        $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])->get(route('super-admin.search', ['q' => 'abc']))->assertSessionHas('sa_mode', 'admin');
    }

    // CL-09 + CL-14 + DS-01 + DS-04

    public function test_label_client_dan_badge_tahap(): void
    {
        $p = $this->proyek();
        $this->assertSame('Dipilih', $this->aplikasi($p, 'lolos')->labelClient());
        $this->assertSame('Menunggu keputusan', $this->aplikasi($p, 'diajukan_ke_client')->labelClient());
        $this->assertSame('Ditolak', $this->aplikasi($p, 'ditolak')->labelClient());
        $this->assertSame(['badge-info', 'badge-aktif'], [CastingProject::TAHAP_BADGES['berjalan'], CastingProject::TAHAP_BADGES['selesai']]);
    }

    public function test_ekspor_client_memuat_peran_grade_dan_status(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id]);
        $kelas = $p->classes()->create(['nama_kelas' => 'Ibu-ibu', 'budget_client' => 100000, 'kuota_kelas' => 2]);
        $app = $this->aplikasi($p, 'lolos');
        $app->update(['casting_project_class_id' => $kelas->id]);
        ClientReview::create(['project_application_id' => $app->id, 'client_id' => $client->id, 'keputusan' => 'approve', 'grade_client' => 'A']);

        $export = new ClientRiwayatExport($client->id, $p->id);
        $baris = $export->collection()->first();

        $this->assertSame(['Alias', 'Peran', 'Grade Anda', 'Keputusan', 'Status', 'Tanggal'], $export->headings());
        $this->assertSame(['Ibu-ibu', 'A', 'Dipilih', 'Dipilih'], [$baris['peran'], $baris['grade'], $baris['keputusan'], $baris['status']]);
    }

    // CL-06

    public function test_daftar_tagihan_client_menampilkan_status_dan_nominal(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id]);
        $p->invoices()->create(['nominal' => 1500000, 'ttd_admin_signature_path' => 'x.png']);

        $this->actingAs($client)->get(route('invoices.index-client'))->assertOk()
            ->assertSee('Belum TTD')->assertSee('Rp 1.500.000');
    }

    // KR-06 + SA-25

    public function test_riwayat_kerja_menampilkan_status_bayar_dan_versi_super_admin(): void
    {
        $korlap = $this->user('korlap');
        $this->actingAs($korlap)->get(route('admin.work-history'))->assertOk()->assertSee('Status Bayar')->assertDontSee('Slip Tersedia');

        $this->actingAs($this->user('super_admin'))->get(route('admin.work-history'))->assertOk()->assertSee('tidak punya honor penugasan');
    }

    // AD-33

    public function test_tombol_undang_tidak_muncul_untuk_akun_tidak_layak(): void
    {
        $admin = $this->user('admin');
        $layak = $this->user('extras', ['username' => 'layak_ok']);
        ExtrasProfile::create(['user_id' => $layak->id, 'foto_profil_path' => 'f.jpg', 'usia' => 25, 'gender' => 'pria', 'tinggi_badan' => 170]);
        $kosong = $this->user('extras', ['username' => 'profil_kosong']);
        ExtrasProfile::create(['user_id' => $kosong->id]);

        $html = $this->actingAs($admin)->get(route('admin.akun.extras'))->assertOk()->getContent();
        $this->assertStringContainsString(route('admin.undangan.form', $layak), $html);
        $this->assertStringNotContainsString(route('admin.undangan.form', $kosong), $html);
    }

    // SA-30

    public function test_seeder_demo_hendra_dan_client_arsip_nonaktif(): void
    {
        $this->seed(DemoLengkapSeeder::class);

        $this->assertSame('nonaktif', User::where('username', 'admin_hendra')->value('status'));
        $this->assertSame(['nonaktif', 'nonaktif'], User::where('email', 'like', '%@arsip.test')->pluck('status')->all());
    }

    // EX-27

    public function test_pesan_undangan_ke_extras_tanpa_kode_internal(): void
    {
        $admin = $this->user('admin');
        $p = $this->proyek($admin, ['nama_produksi' => 'Film Pasar']);
        $app = $this->aplikasi($p, 'diundang');

        $app->kabariUndangan($admin);

        $pesan = $app->extras->user->notifications()->first()->data['pesan'];
        $this->assertStringContainsString('Film Pasar', $pesan);
        $this->assertStringNotContainsString($p->kode_proyek, $pesan);
    }
}
