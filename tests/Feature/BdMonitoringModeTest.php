<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BdMonitoringModeTest extends TestCase
{
    use RefreshDatabase;

    private User $sa;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        $this->sa = User::factory()->create(['role' => 'super_admin', 'name' => 'Fakhrul']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function proyek(string $nama = 'Proyek Andini'): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $this->admin->id,
            'nama_produksi' => $nama,
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
            'status' => 'dibuka',
        ]);
    }

    private function aplikasi(string $status = 'lolos', ?CastingProject $project = null): ProjectApplication
    {
        $extrasUser = User::factory()->create(['role' => 'extras', 'username' => 'dimas.rk']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'nama_asli' => 'Dimas R', 'nik' => '3170000000000001']);

        return ProjectApplication::create([
            'casting_project_id' => ($project ?? $this->proyek())->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
            'fee_final' => 200000,
        ]);
    }

    private function sebagai(User $target): self
    {
        return $this->actingAs($this->sa)->withSession(['sa_mode' => $target->role, 'sa_view_user_id' => $target->id]);
    }

    public function test_non_super_admin_tidak_bisa_mulai_mode(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($this->admin)->get(route('super-admin.mode.pilih', 'client'))->assertForbidden();
        $this->actingAs($this->admin)->post(route('super-admin.mode.mulai'), ['mode' => 'client', 'user_id' => $client->id])
            ->assertForbidden()->assertSessionMissing('sa_view_user_id');
    }

    public function test_view_as_akun_protected_atau_super_admin_ditolak(): void
    {
        $protected = User::factory()->create(['role' => 'client', 'is_protected' => true]);
        $saLain = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($this->sa)->post(route('super-admin.mode.mulai'), ['mode' => 'client', 'user_id' => $protected->id])
            ->assertForbidden()->assertSessionMissing('sa_mode');
        $this->actingAs($this->sa)->post(route('super-admin.mode.mulai'), ['mode' => 'client', 'user_id' => $saLain->id])
            ->assertForbidden();
        $this->actingAs($this->sa)->post(route('super-admin.mode.mulai'), ['mode' => 'super_admin', 'user_id' => $saLain->id])
            ->assertSessionHasErrors('mode');

        $this->actingAs($this->sa)->withSession(['sa_mode' => 'client', 'sa_view_user_id' => $protected->id])
            ->get(route('cd.dashboard'))->assertForbidden();
    }

    public function test_mulai_view_as_client_menampilkan_data_client_target(): void
    {
        $andini = User::factory()->create(['role' => 'client', 'name' => 'Client Andini']);
        $lain = User::factory()->create(['role' => 'client']);
        $this->proyek('Proyek Andini')->cdAssignments()->create(['cd_user_id' => $andini->id]);
        $this->proyek('Proyek Rahasia Lain')->cdAssignments()->create(['cd_user_id' => $lain->id]);

        $this->actingAs($this->sa)->post(route('super-admin.mode.mulai'), ['mode' => 'client', 'user_id' => $andini->id])
            ->assertRedirect('/cd/dashboard')
            ->assertSessionHas('sa_view_user_id', $andini->id);

        $this->get(route('cd.dashboard'))->assertOk()
            ->assertSee('Proyek Andini')->assertDontSee('Proyek Rahasia Lain')
            ->assertSee('Mode lihat saja')->assertSee('Kembali ke Super Admin')
            ->assertSee(route('cd.reviews.index'), false);

        $this->assertDatabaseHas('activity_logs', ['action' => 'SA_MODE_MULAI', 'user_id' => $this->sa->id, 'subject_id' => $andini->id]);
    }

    public function test_post_saat_view_as_client_ditolak_dan_db_tidak_berubah(): void
    {
        $client = User::factory()->create(['role' => 'client', 'name' => 'Nama Lama']);
        $application = $this->aplikasi('diajukan_ke_cd');
        $application->castingProject->cdAssignments()->create(['cd_user_id' => $client->id]);

        $this->sebagai($client)->from('/cd/reviews')->post(route('cd.reviews.review'), [
            'application_ids' => [$application->id], 'keputusan' => 'approve', 'grade_cd' => 'A',
        ])->assertRedirect('/cd/reviews')->assertSessionHas('error');

        $this->sebagai($client)->put(route('cd.profil.update'), ['name' => 'Nama Baru', 'nomor_wa' => '0811'])
            ->assertRedirect()->assertSessionHas('error');

        $this->sebagai($client)->post(route('project-attachments.store', $application->castingProject), [
            'files' => [UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')],
        ])->assertRedirect()->assertSessionHas('error');

        $this->assertSame('diajukan_ke_cd', $application->fresh()->status_partisipasi);
        $this->assertSame('Nama Lama', $client->fresh()->name);
        $this->assertDatabaseCount('cd_reviews', 0);
        $this->assertDatabaseCount('project_attachments', 0);
    }

    public function test_post_saat_view_as_extras_ditolak_dan_db_tidak_berubah(): void
    {
        $application = $this->aplikasi('lolos');
        $extrasUser = $application->extras->user;
        $application->payment()->create(['status' => 'ditransfer']);
        $application->contract()->create([]);
        $lowongan = $this->proyek('Lowongan Baru');

        $this->sebagai($extrasUser)->post(route('payments.confirm', $application))->assertRedirect()->assertSessionHas('error');
        $this->sebagai($extrasUser)->post(route('contracts.sign', $application), ['signature' => 'data:image/png;base64,'.base64_encode('x')])
            ->assertRedirect()->assertSessionHas('error');
        $this->sebagai($extrasUser)->post(route('extras.projects.apply', $lowongan))->assertRedirect()->assertSessionHas('error');
        $this->sebagai($extrasUser)->put(route('extras.profile.update'), ['nama_asli' => 'Diganti'])->assertRedirect()->assertSessionHas('error');
        $this->sebagai($extrasUser)->post(route('extras.negotiations.batalkan', $application))->assertRedirect()->assertSessionHas('error');

        $this->assertSame('ditransfer', $application->payment->fresh()->status);
        $this->assertNull($application->contract->fresh()->ttd_extras_signature_path);
        $this->assertSame('lolos', $application->fresh()->status_partisipasi);
        $this->assertSame(0, $lowongan->applications()->count());
        $this->assertSame('Dimas R', $application->extras->fresh()->nama_asli);
    }

    public function test_get_saat_view_as_extras_render_sebagai_extras_dan_keluar_mode_boleh(): void
    {
        $application = $this->aplikasi('lolos');
        $extrasUser = $application->extras->user;

        $this->sebagai($extrasUser)->get(route('extras.dashboard'))->assertOk()->assertSee('Mode lihat saja');
        $this->sebagai($extrasUser)->get(route('super-admin.dashboard'))->assertOk();

        $this->sebagai($extrasUser)->post(route('super-admin.mode.keluar'))
            ->assertRedirect(route('super-admin.dashboard'))
            ->assertSessionMissing('sa_mode')->assertSessionMissing('sa_view_user_id');

        $this->assertDatabaseHas('activity_logs', ['action' => 'SA_MODE_KELUAR', 'user_id' => $this->sa->id]);
        $this->actingAs($this->sa)->get(route('super-admin.dashboard'))->assertOk()
            ->assertSee(route('super-admin.mode.pilih', 'extras'), false)
            ->assertDontSee('Kembali ke Super Admin');
    }

    public function test_view_as_tidak_aktif_kalau_user_sesi_bukan_super_admin(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->actingAs($this->admin)->withSession(['sa_mode' => 'client', 'sa_view_user_id' => $client->id])
            ->get(route('cd.dashboard'))->assertForbidden()->assertSessionMissing('sa_mode');
    }

    public function test_sa_mode_korlap_validasi_absensi_tercatat_sebagai_korlap(): void
    {
        $application = $this->aplikasi('kontrak_ditandatangani');
        $tanggal = $application->castingProject->shootingDates()->create(['tanggal' => now()->toDateString()]);
        $attendance = Attendance::create([
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $tanggal->id,
            'status' => 'hadir',
            'status_validasi' => 'menunggu',
            'dicatat_oleh' => $application->extras->user_id,
        ]);

        $this->actingAs($this->sa)->post(route('super-admin.mode.mulai'), ['mode' => 'korlap'])->assertRedirect('/admin/absensi');
        $this->get('/dashboard')->assertRedirect('/admin/absensi');
        $this->get(route('admin.attendance.index'))->assertOk()->assertSee('sebagai Korlap');

        $this->post(route('admin.absensi.validasi', $attendance))->assertRedirect();

        $this->assertSame('tervalidasi', $attendance->fresh()->status_validasi);
        $log = ActivityLog::where('action', 'VALIDATE_ATTENDANCE')->sole();
        $this->assertSame($this->sa->id, $log->user_id);
        $this->assertSame('super_admin', $log->role);
        $this->assertSame('korlap', $log->properties['sebagai']);
        $this->assertStringStartsWith('Super Admin Fakhrul memvalidasi', $log->description);
        $this->assertStringEndsWith('(sebagai Korlap)', $log->description);
    }

    public function test_sa_godmode_tandai_transfer_lihat_kontrak_invoice_tapi_tidak_ttd_sebagai_extras(): void
    {
        $application = $this->aplikasi('lolos');
        $application->payment()->create(['status' => 'belum_dibayar']);
        $application->contract()->create([]);

        $this->actingAs($this->sa)->post(route('payments.transfer', $application), [
            'bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 100),
        ])->assertRedirect()->assertSessionHas('status');
        $this->assertSame('ditransfer', $application->payment->fresh()->status);

        $this->actingAs($this->sa)->get(route('payments.show', $application))->assertOk();
        $this->actingAs($this->sa)->get(route('contracts.show', $application))->assertOk();
        $this->actingAs($this->sa)->get(route('invoices.show', $application->castingProject))->assertOk();

        $this->actingAs($this->sa)->post(route('contracts.sign', $application), [
            'signature' => 'data:image/png;base64,'.base64_encode('x'),
        ])->assertRedirect();
        $contract = $application->contract->fresh();
        $this->assertNotNull($contract->ttd_admin_signature_path);
        $this->assertNull($contract->ttd_extras_signature_path);

        $this->actingAs($this->sa)->post(route('payments.confirm', $application))->assertForbidden();
        $this->assertSame('ditransfer', $application->payment->fresh()->status);
    }

    public function test_logout_tetap_boleh_saat_view_as(): void
    {
        $client = User::factory()->create(['role' => 'client']);

        $this->sebagai($client)->post(route('logout'))->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_get_kontrak_saat_view_as_tidak_generate_kontrak(): void
    {
        Mail::fake();
        Http::fake();
        $application = $this->aplikasi('lolos');
        $application->extras->forceFill(['nik' => '3201010101010001'])->save();

        $this->sebagai($application->extras->user)->get(route('contracts.show', $application))
            ->assertRedirect()->assertSessionHas('info');

        $this->assertNull($application->fresh()->contract);
        Mail::assertNothingQueued();
        Http::assertNothingSent();
    }
}
