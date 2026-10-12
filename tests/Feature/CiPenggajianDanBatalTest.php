<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

/** Audit 12 Okt 2026, CI.1 (penggajian staf dari UI + slip) dan CI.3 (batal Extras, sesi, intended). */
class CiPenggajianDanBatalTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'aktif'], $attrs));
    }

    private function proyek(): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $this->user('admin')->id,
            'nama_produksi' => 'Proyek '.fake()->unique()->lexify('????'),
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    private function slip(User $staf, bool $dibayar = false): StaffPayroll
    {
        Storage::fake('local');
        $a = $this->proyek()->adminAssignments()->create(['user_id' => $staf->id, 'assigned_by' => $staf->id, 'status_log' => 'selesai', 'completed_at' => now()]);
        $p = StaffPayroll::create(['admin_project_assignment_id' => $a->id, 'nominal_pokok' => 300000]);
        Storage::disk('local')->put("payrolls/pdf/{$p->id}.pdf", '%PDF-1.4 slip');
        $p->tandaiSlipDibuat("payrolls/pdf/{$p->id}.pdf");
        if ($dibayar) {
            $p->tandaiDibayar();
        }

        return $p;
    }

    // CI.1

    public function test_sa_menugaskan_menyelesaikan_dan_menambah_komponen_dari_ui(): void
    {
        Storage::fake('local');
        $sa = $this->user('super_admin');
        $korlap = $this->user('korlap', ['honor_nominal' => 250000]);
        $p = $this->proyek();

        $this->actingAs($sa)->get(route('admin.projects.show', [$p, 'tab' => 'keuangan']))->assertOk()
            ->assertSee(route('super-admin.assignments.assign', $p), false);

        $this->actingAs($sa)->post(route('super-admin.assignments.assign', $p), ['user_id' => $korlap->id])->assertSessionHas('status');
        $a = $p->adminAssignments()->firstOrFail();
        $this->assertSame('berjalan', $a->status_log);

        $this->actingAs($sa)->get(route('admin.projects.show', [$p, 'tab' => 'keuangan']))
            ->assertSee(route('super-admin.assignments.complete', $a), false);

        $this->actingAs($sa)->post(route('super-admin.assignments.complete', $a))->assertSessionHas('status');
        $payroll = $a->fresh()->payroll;
        $this->assertSame('selesai', $a->fresh()->status_log);
        $this->assertEquals(250000, $payroll->nominal_pokok);
        Storage::disk('local')->assertExists($payroll->pdf_slip_path);

        $this->actingAs($sa)->get(route('admin.projects.show', [$p, 'tab' => 'keuangan']))
            ->assertSee(route('super-admin.payrolls.addon', $payroll), false)
            ->assertSee(route('admin.payrolls.slip', $payroll), false);

        $this->actingAs($sa)->post(route('super-admin.payrolls.addon', $payroll), ['label' => 'Transport', 'nominal' => 50000])->assertSessionHas('status');
        $this->assertEquals(300000, $payroll->fresh()->nominalTotal());
    }

    public function test_admin_tidak_melihat_panel_penugasan_dan_tidak_bisa_memanggil_route(): void
    {
        $admin = $this->user('admin');
        $p = $this->proyek();

        $this->actingAs($admin)->get(route('admin.projects.show', [$p, 'tab' => 'keuangan']))->assertOk()
            ->assertDontSee(route('super-admin.assignments.assign', $p), false);
        $this->actingAs($admin)->post(route('super-admin.assignments.assign', $p), ['user_id' => $admin->id])->assertForbidden();
    }

    public function test_slip_hanya_bisa_diunduh_pemilik_dan_super_admin(): void
    {
        $korlap = $this->user('korlap');
        $slip = $this->slip($korlap);

        $this->actingAs($korlap)->get(route('admin.payrolls.slip', $slip))->assertOk()->assertDownload();
        $this->actingAs($this->user('super_admin'))->get(route('admin.payrolls.slip', $slip))->assertOk();
        $this->actingAs($this->user('korlap'))->get(route('admin.payrolls.slip', $slip))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('admin.payrolls.slip', $slip))->assertForbidden();
        $this->actingAs($this->user('extras'))->get(route('admin.payrolls.slip', $slip))->assertForbidden();
    }

    public function test_riwayat_kerja_hanya_menjumlah_yang_dibayar_dan_menautkan_slip(): void
    {
        $korlap = $this->user('korlap');
        $belum = $this->slip($korlap);

        $this->actingAs($korlap)->get(route('admin.work-history'))->assertOk()
            ->assertSee('Belum diproses')
            ->assertSee('Total Diterima (sudah dibayar): <strong>Rp 0</strong>', false)
            ->assertSee(route('admin.payrolls.slip', $belum), false)
            ->assertDontSee('Slip Tersedia');

        $belum->tandaiDibayar();
        $this->actingAs($korlap)->get(route('admin.work-history'))
            ->assertSee('Total Diterima (sudah dibayar): <strong>Rp 300.000</strong>', false);
    }

    public function test_detail_akun_staf_menampilkan_slip_dan_status_bayar(): void
    {
        $korlap = $this->user('korlap');
        $slip = $this->slip($korlap);

        $this->actingAs($this->user('super_admin'))->get(route('super-admin.admins.show', $korlap))->assertOk()
            ->assertSee('Belum diproses')
            ->assertSee(route('admin.payrolls.slip', $slip), false);
    }

    // CI.3.1 (EX-11)

    private function aplikasi(CastingProject $p, int $hariKeShooting, string $status = 'lolos', ?ExtrasProfile $extras = null): ProjectApplication
    {
        $p->shootingDates()->create(['tanggal' => today()->addDays($hariKeShooting)]);
        $extras ??= ExtrasProfile::create(['user_id' => $this->user('extras')->id]);

        return ProjectApplication::create([
            'casting_project_id' => $p->id, 'extras_id' => $extras->id,
            'status_partisipasi' => $status, 'fee_final' => 200000,
        ]);
    }

    public function test_batal_h3_bukan_mendadak_dan_h1_mendadak_tercatat(): void
    {
        $h3 = $this->aplikasi($this->proyek(), 3);
        $this->actingAs($h3->extras->user)->post(route('extras.negotiations.batalkan', $h3), ['alasan' => 'Sakit'])->assertSessionHas('status');
        $this->assertSame('dibatalkan', $h3->fresh()->status_partisipasi);
        $this->assertFalse((bool) $h3->cancellations()->firstOrFail()->is_mendadak);
        $this->assertSame(0, $h3->extras->batalMendadak()->count());

        $h1 = $this->aplikasi($this->proyek(), 1, 'kontrak_ditandatangani');
        $this->actingAs($h1->extras->user)->post(route('extras.negotiations.batalkan', $h1), ['alasan' => 'Keluarga'])->assertSessionHas('status');
        $cancel = $h1->cancellations()->firstOrFail();
        $this->assertTrue((bool) $cancel->is_mendadak);
        $this->assertSame('extras', $cancel->dibatalkan_oleh);
        $this->assertSame(1, $h1->extras->batalMendadak()->count());
    }

    public function test_batal_mendadak_tiga_kali_lewat_http_membuat_akun_melanggar(): void
    {
        $extras = ExtrasProfile::create(['user_id' => $this->user('extras')->id]);
        $user = $extras->user;

        foreach (range(1, 3) as $i) {
            $app = $this->aplikasi($this->proyek(), 1, 'lolos', $extras);
            $this->actingAs($user)->post(route('extras.negotiations.batalkan', $app), ['alasan' => "Batal {$i}"]);
            $this->assertSame($i, $extras->batalMendadak()->count());
        }

        $this->assertSame('melanggar', $extras->fresh()->status);
    }

    public function test_batal_wajib_alasan(): void
    {
        $app = $this->aplikasi($this->proyek(), 5);

        $this->actingAs($app->extras->user)->post(route('extras.negotiations.batalkan', $app), ['alasan' => ''])->assertSessionHasErrors('alasan');
        $this->assertSame('lolos', $app->fresh()->status_partisipasi);
    }

    // CI.3.2 (CF.8)

    public function test_sa_mode_lihat_tidak_ikut_terputus_walau_akun_yang_dilihat_nonaktif(): void
    {
        $sa = $this->user('super_admin');
        $client = $this->user('client');
        $client->update(['status' => 'nonaktif']);

        $this->actingAs($sa)->withSession(['sa_mode' => 'client', 'sa_view_user_id' => $client->id])
            ->get(route('admin.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($sa);

        $this->actingAs($sa)->withSession(['sa_mode' => 'admin'])->get(route('admin.dashboard'))->assertOk();
        $this->assertAuthenticatedAs($sa);
    }

    public function test_extras_melanggar_dengan_sesi_aktif_dikeluarkan(): void
    {
        $u = $this->user('extras');
        $profil = ExtrasProfile::create(['user_id' => $u->id]);
        $this->actingAs($u)->get(route('extras.dashboard'))->assertOk();

        $profil->forceFill(['status' => 'melanggar'])->save();
        $this->actingAs($u->fresh())->get(route('extras.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_google_extras_dari_tautan_lowongan_kembali_ke_lowongan(): void
    {
        config(['services.google.client_id' => 'id-tes', 'services.google.client_secret' => 'rahasia']);
        $e = $this->user('extras', ['email' => 'ex@gmail.com']);
        ExtrasProfile::create(['user_id' => $e->id]);
        Socialite::shouldReceive('driver->user')->andReturn((new SocialiteUser)->map(['id' => 'g-9', 'email' => 'ex@gmail.com', 'name' => 'Ex']));

        $this->withSession(['url.intended' => url('/extras/lowongan/7')])
            ->get('/auth/google/callback')->assertRedirect('/extras/lowongan/7');
        $this->assertAuthenticatedAs($e);
    }

    public function test_login_biasa_extras_dari_tautan_lowongan_kembali_ke_lowongan(): void
    {
        $e = $this->user('extras', ['email' => 'ex2@jbtb.test', 'password' => 'rahasia123']);
        ExtrasProfile::create(['user_id' => $e->id]);

        $this->withSession(['url.intended' => url('/extras/lowongan/7')])
            ->post(route('login'), ['email' => 'ex2@jbtb.test', 'password' => 'rahasia123'])->assertRedirect('/extras/lowongan/7');
    }
}
