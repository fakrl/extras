<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Audit 12 Okt 2026, batch A: guard data, uang, dan akses. */
class AuditAGuardTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

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

    private function aplikasi(CastingProject $project, string $status, ?string $username = null): ProjectApplication
    {
        $u = $this->user('extras', $username ? ['username' => $username] : []);
        $extras = ExtrasProfile::create(['user_id' => $u->id]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
            'fee_final' => 200000,
        ]);
    }

    // EX-01 + AD-24

    public function test_extras_tidak_bisa_menambah_addon_dan_form_disembunyikan(): void
    {
        $app = $this->aplikasi($this->proyek(), 'lolos');
        $app->payment()->create(['status' => 'belum_dibayar']);
        $extrasUser = $app->extras->user;

        $this->actingAs($extrasUser)->post(route('payments.addon', $app), ['label' => 'x', 'nominal' => 5000000])->assertForbidden();
        $this->assertDatabaseCount('payment_addons', 0);
        $this->actingAs($extrasUser)->get(route('payments.show', $app))->assertOk()->assertDontSee('Tambah Komponen');
    }

    public function test_addon_ditolak_setelah_transfer_atau_sengketa_dan_form_hilang(): void
    {
        $admin = $this->user('admin');
        foreach (['ditransfer', 'disengketakan'] as $status) {
            $app = $this->aplikasi($this->proyek($admin), 'lolos');
            $app->payment()->create(['status' => $status]);

            $this->actingAs($admin)->post(route('payments.addon', $app), ['label' => 'x', 'nominal' => 1000])->assertSessionHas('error');
            $this->assertSame(0, $app->payment->addons()->count());
            $this->actingAs($admin)->get(route('payments.show', $app))->assertDontSee('Tambah Komponen');
        }
    }

    public function test_addon_admin_menampilkan_penambah(): void
    {
        $admin = $this->user('admin', ['name' => 'Yoga Penambah']);
        $app = $this->aplikasi($this->proyek($admin), 'lolos');
        $app->payment()->create(['status' => 'belum_dibayar']);

        $this->actingAs($admin)->post(route('payments.addon', $app), ['label' => 'Transport', 'nominal' => 50000])->assertSessionHas('status');
        $this->actingAs($admin)->get(route('payments.show', $app))->assertSee('Ditambah oleh Yoga Penambah');
    }

    // EX-02 + AD-01 + CL-07

    public function test_proyek_belum_acc_tidak_muncul_dan_tidak_bisa_dibuka(): void
    {
        $admin = $this->user('admin');
        $client = $this->user('client');
        foreach (['menunggu_acc', 'ditolak'] as $status) {
            $p = $this->proyek($admin, ['client_id' => $client->id, 'nama_produksi' => "Belum ACC {$status}", 'client_request_status' => $status]);

            $this->assertFalse(CastingProject::lowonganTerbuka()->contains('id', $p->id));
            $this->actingAs($this->user('extras'))->get(route('extras.projects.show', $p))->assertNotFound();
            $this->actingAs($admin)->patch(route('admin.projects.toggle-status', $p))->assertSessionHas('error');
            $this->assertSame('dibuka', $p->fresh()->status);

            $this->actingAs($client)->get(route('client.jadwal.index'))->assertDontSee($p->nama_produksi);
            $this->actingAs($client)->get(route('client.reviews.index'))->assertDontSee($p->nama_produksi);
            $this->actingAs($client)->get(route('invoices.index-client'))->assertDontSee($p->nama_produksi);
            $this->actingAs($client)->get(route('client.jadwal.show', $p))->assertNotFound();
            $this->actingAs($admin)->get(route('admin.projects.index'))->assertDontSee('title="Tutup lowongan"', false);
        }
    }

    public function test_proyek_acc_tetap_muncul(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id, 'nama_produksi' => 'Sudah ACC Jaya']);

        $this->assertTrue(CastingProject::lowonganTerbuka()->contains('id', $p->id));
        $this->actingAs($client)->get(route('client.jadwal.index'))->assertSee('Sudah ACC Jaya');
    }

    // KR-01..05

    private function korlapSetup(): array
    {
        $korlap = $this->user('korlap');
        $milik = $this->proyek(null, ['nama_produksi' => 'Proyek Milik Korlap']);
        $lain = $this->proyek(null, ['nama_produksi' => 'Proyek Orang Lain']);
        $milik->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $korlap->id, 'status_log' => 'berjalan']);
        foreach ([$milik, $lain] as $p) {
            $p->shootingDates()->create(['tanggal' => today()]);
        }

        return [$korlap, $milik, $lain];
    }

    public function test_korlap_hanya_melihat_proyek_penugasannya(): void
    {
        [$korlap, $milik, $lain] = $this->korlapSetup();

        $this->actingAs($korlap)->get(route('admin.attendance.index'))
            ->assertOk()->assertSee('Proyek Milik Korlap')->assertDontSee('Proyek Orang Lain');
        $this->actingAs($korlap)->get(route('admin.attendance.index', ['project' => $lain->id]))->assertForbidden();
        $this->actingAs($this->user('admin'))->get(route('admin.attendance.index', ['project' => $lain->id]))->assertOk();
    }

    public function test_korlap_tidak_bisa_aksi_absensi_di_proyek_lain(): void
    {
        Storage::fake('local');
        [$korlap, , $lain] = $this->korlapSetup();
        $app = $this->aplikasi($lain, 'lolos');
        $sd = $lain->shootingDates()->first();

        $this->actingAs($korlap)->post(route('admin.attendance.store', $app), ['event_shooting_date_id' => $sd->id, 'status' => 'hadir'])->assertForbidden();

        Storage::disk('local')->put('absen.jpg', 'x');
        $absen = Attendance::create(['project_application_id' => $app->id, 'event_shooting_date_id' => $sd->id, 'status' => 'hadir', 'status_validasi' => 'menunggu', 'foto_path' => 'absen.jpg', 'dicatat_oleh' => $korlap->id]);
        $this->actingAs($korlap)->post(route('admin.absensi.validasi', $absen))->assertForbidden();
        $this->actingAs($korlap)->post(route('admin.absensi.tolak', $absen), ['alasan' => 'x'])->assertForbidden();
        $this->actingAs($korlap)->get(route('admin.absensi.foto', $absen))->assertForbidden();
        $this->assertSame('menunggu', $absen->fresh()->status_validasi);
    }

    public function test_korlap_catatan_hanya_proyek_penugasan_dan_status_lolos(): void
    {
        [$korlap, $milik, $lain] = $this->korlapSetup();
        $data = ['jenis' => 'sanksi', 'isi' => 'Terlambat.'];

        $this->actingAs($korlap)->post(route('admin.applications.catatan', $this->aplikasi($lain, 'lolos')), $data)->assertForbidden();
        $this->actingAs($korlap)->post(route('admin.applications.catatan', $this->aplikasi($milik, 'diajukan')), $data)->assertSessionHas('error');
        $this->assertDatabaseCount('field_notes', 0);
        $this->actingAs($korlap)->post(route('admin.applications.catatan', $this->aplikasi($milik, 'lolos')), $data)->assertSessionHas('status');
        $this->assertDatabaseCount('field_notes', 1);
    }

    public function test_absensi_hanya_peserta_lolos_ke_atas_dan_bukan_tanggal_depan(): void
    {
        [$korlap, $milik] = $this->korlapSetup();
        $depan = $milik->shootingDates()->create(['tanggal' => today()->addDays(3)]);
        $hariIni = $milik->shootingDates()->whereDate('tanggal', today())->first();
        $diundang = $this->aplikasi($milik, 'diundang', 'siundang');
        $deal = $this->aplikasi($milik, 'deal', 'sideal');
        $lolos = $this->aplikasi($milik, 'lolos', 'silolos');

        $this->actingAs($korlap)->get(route('admin.attendance.index', ['project' => $milik->id, 'tanggal' => $hariIni->id]))
            ->assertSee('silolos')->assertDontSee('sideal')->assertDontSee('siundang');

        foreach ([$diundang, $deal] as $a) {
            $this->actingAs($korlap)->post(route('admin.attendance.store', $a), ['event_shooting_date_id' => $hariIni->id, 'status' => 'hadir'])->assertSessionHas('error');
        }
        $this->actingAs($korlap)->post(route('admin.attendance.store', $lolos), ['event_shooting_date_id' => $depan->id, 'status' => 'hadir'])->assertSessionHas('error');
        $this->assertDatabaseCount('attendances', 0);

        $this->actingAs($korlap)->post(route('admin.attendance.store', $lolos), ['event_shooting_date_id' => $hariIni->id, 'status' => 'hadir'])->assertSessionHas('status');
        $this->assertDatabaseCount('attendances', 1);
    }

    // EX-03 + EX-04

    public function test_selfie_extras_dipaksa_hari_ini_dan_gagal_dengan_pesan(): void
    {
        Storage::fake('local');
        $p = $this->proyek();
        $besok = $p->shootingDates()->create(['tanggal' => today()->addDay()]);
        $app = $this->aplikasi($p, 'lolos');
        $user = $app->extras->user;
        $foto = fn () => UploadedFile::fake()->image('s.jpg');

        $this->actingAs($user)->post(route('extras.absensi.selfie', $app), ['foto' => $foto(), 'event_shooting_date_id' => $besok->id])->assertSessionHas('error');
        $this->assertDatabaseCount('attendances', 0);

        $hariIni = $p->shootingDates()->create(['tanggal' => today()]);
        $this->actingAs($user)->post(route('extras.absensi.selfie', $app), ['foto' => $foto(), 'event_shooting_date_id' => $besok->id])->assertSessionHas('status');
        $this->assertSame($hariIni->id, Attendance::first()->event_shooting_date_id);

        $kontrak = $this->aplikasi($p, 'deal');
        $this->actingAs($kontrak->extras->user)->post(route('extras.absensi.selfie', $kontrak), ['foto' => $foto()])->assertSessionHas('error');
    }

    public function test_tombol_selfie_hanya_di_hari_shooting(): void
    {
        $p = $this->proyek();
        $p->shootingDates()->create(['tanggal' => today()->addDays(5)]);
        $app = $this->aplikasi($p, 'lolos');

        $this->actingAs($app->extras->user)->get(route('extras.dashboard'))->assertOk()->assertDontSee('Absen Selfie On-Site');
        $p->shootingDates()->create(['tanggal' => today()]);
        $this->actingAs($app->extras->user)->get(route('extras.dashboard'))->assertSee('Absen Selfie On-Site')
            ->assertSee('Ambil foto langsung di lokasi')->assertDontSee('Hanya kamera langsung');
    }

    // CL-02

    public function test_client_hanya_lihat_media_extras_yang_diajukan_ke_proyeknya(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('f.jpg', 'x');
        $client = $this->user('client');
        $milik = $this->proyek(null, ['client_id' => $client->id]);
        $lain = $this->proyek(null, ['client_id' => $this->user('client')->id]);

        $diajukan = $this->aplikasi($milik, 'diajukan_ke_client')->extras;
        $diajukan->forceFill(['foto_profil_path' => 'f.jpg'])->save();
        $orangLain = $this->aplikasi($lain, 'diajukan_ke_client')->extras;
        $orangLain->forceFill(['foto_profil_path' => 'f.jpg'])->save();
        $belum = $this->aplikasi($milik, 'diajukan')->extras;
        $belum->forceFill(['foto_profil_path' => 'f.jpg'])->save();

        $this->actingAs($client)->get(route('extras.media.foto', $diajukan))->assertOk();
        $this->actingAs($client)->get(route('extras.media.foto', $orangLain))->assertForbidden();
        $this->actingAs($client)->get(route('extras.media.foto', $belum))->assertForbidden();
    }

    // CL-01 + CL-04

    public function test_client_simpan_jadwal_dengan_daftar_panggilan(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id]);
        $sd = $p->shootingDates()->create(['tanggal' => '2026-11-10']);

        $this->actingAs($client)->get(route('client.jadwal.show', $p))->assertSee('panggilan[0][nama]', false);

        $this->actingAs($client)->post(route('client.jadwal.store', $p), [
            'tanggal' => '2026-11-10', 'lokasi' => 'Studio A',
            'panggilan' => [
                0 => ['nama' => 'Ibu-ibu', 'jam' => '06:30'],
                1 => ['nama' => '', 'jam' => ''],
                3 => ['nama' => 'Bapak', 'jam' => '07:00'],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect(route('client.jadwal.show', $p));

        $sd->refresh();
        $this->assertSame('Studio A', $sd->lokasi);
        $this->assertEquals([['nama' => 'Ibu-ibu', 'jam' => '06:30'], ['nama' => 'Bapak', 'jam' => '07:00']], $sd->panggilan);
    }

    public function test_client_tidak_bisa_membuat_tanggal_baru(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id]);
        $p->shootingDates()->create(['tanggal' => '2026-11-10']);

        $this->actingAs($client)->post(route('client.jadwal.store', $p), ['tanggal' => '2026-12-25', 'lokasi' => 'X'])->assertSessionHas('error');
        $this->assertSame(1, $p->shootingDates()->count());
    }

    // CL-05

    public function test_ttd_invoice_client_tidak_bisa_ditimpa_dan_harus_png(): void
    {
        Storage::fake('local');
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id]);

        $this->actingAs($client)->post(route('invoices.sign', $p), ['signature' => 'data:image/png;base64,'])->assertSessionHas('error');
        $this->actingAs($client)->post(route('invoices.sign', $p), ['signature' => 'data:image/png;base64,'.base64_encode('bukan png')])->assertSessionHas('error');
        $this->assertNull($p->invoices()->first()?->ttd_client_signature_path);

        $this->actingAs($client)->post(route('invoices.sign', $p), ['signature' => 'data:image/png;base64,'.self::PNG])->assertSessionHas('status');
        $path = $p->invoices()->first()->ttd_client_signature_path;
        $this->assertNotNull($path);

        $this->actingAs($client)->post(route('invoices.sign', $p), ['signature' => 'data:image/png;base64,'.self::PNG])->assertSessionHas('error');
        $this->assertSame($path, $p->invoices()->first()->ttd_client_signature_path);
    }

    public function test_ttd_invoice_ditolak_bila_sudah_lunas(): void
    {
        Storage::fake('local');
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id]);
        $p->invoices()->create(['status_bayar' => 'lunas']);

        $this->actingAs($client)->post(route('invoices.sign', $p), ['signature' => 'data:image/png;base64,'.self::PNG])->assertSessionHas('error');
        $this->assertNull($p->invoices()->first()->ttd_client_signature_path);
    }

    // SA-04 + SA-05

    public function test_akun_nonaktif_dengan_sesi_aktif_dikeluarkan(): void
    {
        $user = $this->user('admin');
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();

        $user->update(['status' => 'nonaktif']);
        $this->actingAs($user->fresh())->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_ganti_role_perlu_konfirmasi_dan_tercatat_lama_ke_baru(): void
    {
        $sa = $this->user('super_admin', ['is_protected' => true]);
        $target = $this->user('admin', ['email' => 'a@example.com']);
        $data = ['name' => $target->name, 'email' => 'a@example.com', 'role' => 'korlap'];

        $this->actingAs($sa)->patch(route('super-admin.admins.update', $target), $data)->assertSessionHas('error');
        $this->assertSame('admin', $target->fresh()->role);

        $this->actingAs($sa)->patch(route('super-admin.admins.update', $target), $data + ['konfirmasi_role' => 1])->assertSessionHas('status');
        $this->assertSame('korlap', $target->fresh()->role);
        $this->assertTrue(ActivityLog::where('action', 'UPDATE_USER')->where('description', 'like', '%admin → korlap%')->exists());
    }

    public function test_ganti_nama_tanpa_ganti_role_tidak_perlu_konfirmasi(): void
    {
        $sa = $this->user('super_admin', ['is_protected' => true]);
        $target = $this->user('admin', ['email' => 'a@example.com']);

        $this->actingAs($sa)->patch(route('super-admin.admins.update', $target), ['name' => 'Baru', 'email' => 'a@example.com', 'role' => 'admin'])->assertSessionHas('status');
        $this->assertSame('Baru', $target->fresh()->name);
    }

    // AD-04 + AD-28

    private function payroll(User $staf): StaffPayroll
    {
        $a = $this->proyek()->adminAssignments()->create(['user_id' => $staf->id, 'assigned_by' => $staf->id, 'status_log' => 'selesai']);

        return StaffPayroll::create(['admin_project_assignment_id' => $a->id, 'nominal_pokok' => 300000]);
    }

    public function test_admin_tidak_bisa_tandai_honor_sendiri_dibayar(): void
    {
        $admin = $this->user('admin');
        $milikSendiri = $this->payroll($admin);
        $milikLain = $this->payroll($this->user('korlap'));

        $this->actingAs($admin)->patch(route('admin.payrolls.tandai-dibayar', $milikSendiri))->assertSessionHas('error');
        $this->assertFalse($milikSendiri->fresh()->isDibayar());
        $this->actingAs($admin)->patch(route('admin.payrolls.tandai-dibayar', $milikLain))->assertSessionHas('status');
        $this->assertTrue($milikLain->fresh()->isDibayar());

        $this->actingAs($this->user('super_admin'))->patch(route('admin.payrolls.tandai-dibayar', $milikSendiri))->assertSessionHas('status');
        $this->assertTrue($milikSendiri->fresh()->isDibayar());
    }

    public function test_tandai_lunas_memakai_nominal_tersimpan_terbaru(): void
    {
        $admin = $this->user('admin');
        $p = $this->proyek($admin);
        $p->invoices()->create(['nominal' => 500000]);

        $this->actingAs($admin)->patch(route('admin.projects.invoice-lunas', $p), ['nominal' => 100000])->assertSessionHas('status');
        $invoice = $p->invoices()->first();
        $this->assertTrue($invoice->isLunas());
        $this->assertEquals(500000, $invoice->nominal);
    }

    // AD-02 + AD-03 + DS-06 + EX-05 + EX-12

    private function nego(string $status, string $oleh = 'admin', string $aksi = 'tawar', array $proyek = []): ProjectApplication
    {
        $app = $this->aplikasi($this->proyek(null, $proyek), $status);
        $app->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => $oleh, 'nominal' => 250000, 'aksi' => $aksi]);

        return $app;
    }

    public function test_admin_tidak_bisa_terima_atau_counter_tawarannya_sendiri(): void
    {
        $admin = $this->user('admin');
        $app = $this->nego('nego_fee', 'admin');

        $this->actingAs($admin)->get(route('admin.negotiations.show', $app))
            ->assertSee('Menunggu balasan Extras sejak')->assertDontSee('Terima Rp')->assertSee('Penawaran')->assertDontSee('>nego_fee<', false);
        $this->actingAs($admin)->post(route('admin.negotiations.terima', $app), ['nominal' => 250000])->assertSessionHas('error');
        $this->actingAs($admin)->post(route('admin.negotiations.counter', $app), ['nominal' => 300000])->assertSessionHas('error');
        $this->assertSame('nego_fee', $app->fresh()->status_partisipasi);
        $this->assertSame(1, $app->feeNegotiations()->count());
    }

    public function test_admin_bisa_terima_counter_extras(): void
    {
        $app = $this->nego('nego_fee', 'extras', 'counter');

        $this->actingAs($this->user('admin'))->get(route('admin.negotiations.show', $app))->assertSee('Terima Rp');
        $this->actingAs($this->user('admin'))->post(route('admin.negotiations.terima', $app), ['nominal' => 250000])->assertSessionHas('status');
        $this->assertSame('deal', $app->fresh()->status_partisipasi);
    }

    public function test_aksi_nego_setelah_kontrak_tampil_fee_final_dan_pesan_bukan_422(): void
    {
        $admin = $this->user('admin');
        $app = $this->nego('kontrak_ditandatangani', 'extras', 'counter');

        $this->actingAs($admin)->get(route('admin.negotiations.show', $app))->assertSee('Fee final Rp 200.000')->assertDontSee('Terima Rp')->assertDontSee('Hentikan Negosiasi');
        foreach ([['terima', ['nominal' => 1]], ['counter', ['nominal' => 1]], ['tolak', ['alasan' => 'x']]] as [$aksi, $data]) {
            $this->actingAs($admin)->post(route("admin.negotiations.{$aksi}", $app), $data)->assertSessionHas('error');
        }
        $this->actingAs($app->extras->user)->post(route('extras.negotiations.terima', $app))->assertSessionHas('error');
    }

    public function test_extras_tolak_penawaran_fee_tetap(): void
    {
        $tetap = $this->nego('nego_fee', 'admin', 'tawar', ['nego_terbuka' => false]);
        $user = $tetap->extras->user;

        $this->actingAs($user)->get(route('extras.negotiations.show', $tetap))->assertSee('Tolak Penawaran');
        $this->actingAs($user)->post(route('extras.negotiations.tolak', $tetap), ['alasan' => 'Kurang'])->assertSessionHas('status');
        $this->assertSame('ditolak', $tetap->fresh()->status_partisipasi);
        $this->assertSame(0, $tetap->cancellations()->count());

        $terbuka = $this->nego('nego_fee', 'admin', 'tawar', ['nego_terbuka' => true]);
        $this->actingAs($terbuka->extras->user)->post(route('extras.negotiations.tolak', $terbuka))->assertSessionHas('error');
        $this->assertSame('nego_fee', $terbuka->fresh()->status_partisipasi);
    }

    public function test_pesan_gagal_memakai_error_bukan_status(): void
    {
        $app = $this->aplikasi($this->proyek(), 'lolos');

        $this->actingAs($this->user('admin'))->patch(route('admin.applications.reject', $app), ['alasan_tolak' => 'x'])
            ->assertSessionHas('error')->assertSessionMissing('status');
        $diajukan = $this->aplikasi($this->proyek(), 'diajukan');
        $this->actingAs($diajukan->extras->user)->post(route('extras.negotiations.batalkan', $diajukan), ['alasan' => 'x'])
            ->assertSessionHas('error')->assertSessionMissing('status');
    }

    // SA-01

    public function test_kinerja_admin_menghitung_penugasan_selesai(): void
    {
        $admin = $this->user('admin');
        $this->proyek()->adminAssignments()->create(['user_id' => $admin->id, 'assigned_by' => $admin->id, 'status_log' => 'selesai']);
        $this->proyek()->adminAssignments()->create(['user_id' => $admin->id, 'assigned_by' => $admin->id, 'status_log' => 'berjalan']);

        $html = $this->actingAs($this->user('super_admin'))->get(route('super-admin.admins.show', $admin))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#>1</div><div[^>]*>Selesai</div>#', $html);
    }

    // AD-05

    public function test_form_proyek_mengisi_ulang_peran_dan_tanggal_saat_validasi_gagal(): void
    {
        $admin = $this->user('admin');
        $client = $this->user('client');
        $kelas = [
            0 => ['nama_kelas' => 'Peran Satu', 'budget_client' => 100000, 'kuota_kelas' => 2],
            1 => ['nama_kelas' => 'Peran Dua', 'budget_client' => 150000, 'kuota_kelas' => 3],
        ];

        $this->actingAs($admin)->from(route('admin.projects.create'))->post(route('admin.projects.store'), [
            'nama_produksi' => 'Gagal Validasi', 'client_id' => $client->id, 'kuota' => 5,
            'tanggal_shooting' => ['2026-11-01', '2026-11-02'], 'kelas' => $kelas,
        ])->assertSessionHasErrors('deadline');

        $this->actingAs($admin)->get(route('admin.projects.create'))
            ->assertSee('Peran Satu')->assertSee('Peran Dua')->assertSee('value="2026-11-01"', false)->assertSee('value="2026-11-02"', false);
    }

    // CL-03 + AD-07

    public function test_client_tidak_melihat_ditolak_dini_admin_tapi_melihat_penolakannya_sendiri(): void
    {
        $client = $this->user('client');
        $p = $this->proyek(null, ['client_id' => $client->id]);
        $this->aplikasi($p, 'ditolak', 'tolakdini');
        $olehClient = $this->aplikasi($p, 'ditolak', 'tolakclient');
        ClientReview::create(['project_application_id' => $olehClient->id, 'client_id' => $client->id, 'keputusan' => 'reject']);

        $this->actingAs($client)->get(route('client.reviews.show', $p))->assertSee('tolakclient')->assertDontSee('tolakdini');
    }

    public function test_tab_sudah_ke_client_admin(): void
    {
        $admin = $this->user('admin');
        $p = $this->proyek($admin, ['client_id' => $this->user('client')->id]);
        $this->aplikasi($p, 'ditolak', 'tolakdini');
        $this->aplikasi($p, 'kontrak_ditandatangani', 'sudahkontrak');
        $this->aplikasi($p, 'diajukan_ke_client', 'sedangdiajukan');

        $this->actingAs($admin)->get(route('admin.projects.applicants', [$p, 'tab' => 'client']))
            ->assertSee('sudahkontrak')->assertSee('sedangdiajukan')->assertDontSee('tolakdini');
    }

    // EX-06

    public function test_step_bar_extras_menunggu_client_dan_lolos_selesai(): void
    {
        $p = $this->proyek();
        $diajukan = $this->aplikasi($p, 'diajukan_ke_client');
        $html = view('partials.application-progress', ['app' => $diajukan])->render();
        $this->assertStringContainsString('Menunggu Client', $html);
        $this->assertStringNotContainsString('Dipilih Client', $html);
        $this->assertSame(2, substr_count($html, 'is-done'));

        $lolos = view('partials.application-progress', ['app' => $this->aplikasi($p, 'lolos')])->render();
        $this->assertStringContainsString('Dipilih Client', $lolos);
        $this->assertSame(3, substr_count($lolos, 'is-done'));
    }
}
