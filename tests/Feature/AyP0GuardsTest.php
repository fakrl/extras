<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * SPEC AY.1: regresi untuk bug P0 yang dibereskan bagian ini.
 */
class AyP0GuardsTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(User $admin, ExtrasProfile $extras, string $status, array $extra = []): ProjectApplication
    {
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek AY Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        return ProjectApplication::create(array_merge([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
        ], $extra));
    }

    public function test_proyek_keuangan_dengan_data_payroll_dan_payment_200(): void
    {
        // Item 1: relasi salah di KeuanganService bikin halaman ini crash
        // (assignment.project & application.* tidak ada). Regresi eager-load.
        $admin = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $application = $this->buatAplikasi($admin, $extras, 'lolos', ['fee_final' => 200000]);
        $application->payment()->create(['status' => 'ditransfer']);

        $korlapProject = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Payroll',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
        $assignment = $korlapProject->adminAssignments()->create([
            'user_id' => $korlap->id,
            'assigned_by' => $admin->id,
            'status_log' => 'selesai',
        ]);
        StaffPayroll::create([
            'admin_project_assignment_id' => $assignment->id,
            'nominal_pokok' => 300000,
        ]);

        $this->actingAs($admin)->get(route('admin.projects.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.projects.show', [$korlapProject, 'tab' => 'keuangan']))->assertOk();
        $this->actingAs($admin)->get(route('admin.projects.show', [$application->casting_project_id, 'tab' => 'keuangan']))->assertOk();
    }

    public function test_sign_setelah_batalkan_status_tetap_dibatalkan(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = $this->buatAplikasi($admin, $extras, 'lolos', ['fee_final' => 200000]);
        $application->contract()->create([]);

        $application->batalkan('admin', 'Proyek dibatalkan client.');

        $response = $this->actingAs($extrasUser)->post(route('contracts.sign', $application), [
            'signature' => 'data:image/png;base64,'.base64_encode('fake'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $application->refresh();
        $this->assertSame('dibatalkan', $application->status_partisipasi);
        $this->assertNull($application->contract->fresh()->ttd_extras_signature_path);
    }

    public function test_transfer_ulang_setelah_dikonfirmasi_diterima_ditolak(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = $this->buatAplikasi($admin, $extras, 'lolos', ['fee_final' => 200000]);
        $application->payment()->create([
            'status' => 'dikonfirmasi_diterima',
            'bukti_transfer_path' => 'payments/bukti-transfer/lama.jpg',
        ]);

        $response = $this->actingAs($admin)->post(route('payments.transfer', $application), [
            'bukti_transfer' => UploadedFile::fake()->create('baru.pdf', 100),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $application->payment->refresh();
        $this->assertSame('dikonfirmasi_diterima', $application->payment->status);
        $this->assertSame('payments/bukti-transfer/lama.jpg', $application->payment->bukti_transfer_path);
    }

    public function test_transfer_ditolak_kalau_payment_belum_ada(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = $this->buatAplikasi($admin, $extras, 'lolos', ['fee_final' => 200000]);

        $response = $this->actingAs($admin)->post(route('payments.transfer', $application), [
            'bukti_transfer' => UploadedFile::fake()->create('baru.pdf', 100),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_setgrade_tidak_mundurkan_status_selain_diajukan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = $this->buatAplikasi($admin, $extras, 'lolos', ['fee_final' => 200000]);

        $response = $this->actingAs($admin)->patch(route('admin.applications.grade', $application), [
            'grade' => 'A',
        ]);

        $response->assertRedirect();
        $application->refresh();
        $this->assertSame('lolos', $application->status_partisipasi);
        $this->assertSame('A', $application->grade);
    }

    public function test_ajukan_fee_awal_ditolak_untuk_aplikasi_yang_sudah_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = $this->buatAplikasi($admin, $extras, 'diajukan');
        $application->tolakDini('Tidak sesuai kriteria.');

        $response = $this->actingAs($admin)->post(route('admin.negotiations.ajukan', $application), [
            'nominal' => 200000,
        ]);

        $response->assertRedirect();
        $application->refresh();
        $this->assertSame('ditolak', $application->status_partisipasi);
    }

    public function test_selfie_ditolak_setelah_absensi_tervalidasi(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);
        $application = $this->buatAplikasi($admin, $extras, 'lolos');
        $shootingDate = $application->castingProject->shootingDates()->create(['tanggal' => today()]);

        $this->actingAs($extrasUser)->post(route('extras.absensi.selfie', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'foto' => UploadedFile::fake()->image('selfie.jpg'),
        ]);

        $attendance = $application->attendances()->first();
        $this->actingAs($korlap)->post(route('admin.absensi.validasi', $attendance));

        $response = $this->actingAs($extrasUser)->post(route('extras.absensi.selfie', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'foto' => UploadedFile::fake()->image('selfie-baru.jpg'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $attendance->refresh();
        $this->assertSame('tervalidasi', $attendance->status_validasi);
    }

    public function test_kuota_penuh_mengabaikan_aplikasi_ditolak_dan_dibatalkan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Kuota',
            'deadline' => now()->addDays(7),
            'kuota' => 1,
        ]);

        $extrasDitolak = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
        $project->applications()->create([
            'extras_id' => $extrasDitolak->id,
            'status_partisipasi' => 'ditolak',
        ]);

        $this->assertFalse($project->fresh()->kuotaPenuh());
    }

    public function test_kelola_akun_tidak_ada_form_bersarang(): void
    {
        // Item 5: form Reset Password/Edit/Hapus/Restore per-akun dulu ada
        // di dalam <form id="bulk-form">. Browser buang tag form dalam,
        // submit-nya bisa nyasar. Cek struktural pakai DOMDocument.
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        User::factory()->create(['role' => 'admin', 'name' => 'Staf Satu']);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.akun.index'));
        $response->assertOk();

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($response->getContent());
        libxml_use_internal_errors(false);

        foreach ($dom->getElementsByTagName('form') as $form) {
            $this->assertSame(
                0,
                $form->getElementsByTagName('form')->length,
                'Ditemukan <form> bersarang di dalam <form> lain.'
            );
        }

        $this->assertStringContainsString('class="bulk-cb" form="bulk-form"', $response->getContent());

        ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
        $extrasPage = $this->actingAs($superAdmin)->get(route('super-admin.akun.index', ['role' => 'extras']))->assertOk();
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($extrasPage->getContent());
        libxml_use_internal_errors(false);
        foreach ($dom->getElementsByTagName('form') as $form) {
            $this->assertSame(0, $form->getElementsByTagName('form')->length, 'Form bersarang di mode kartu Extras.');
        }
    }

    public function test_tolak_pengajuan_yang_sudah_disetujui_ditolak(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = CastingProject::create([
            'admin_id' => $sa->id,
            'nama_produksi' => 'Proyek Sudah Jalan',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
            'status' => 'dibuka',
            'client_request_status' => 'disetujui',
        ]);

        $this->actingAs($sa)->post(route('super-admin.projects.reject', $project), ['alasan_tolak' => 'Salah klik'])
            ->assertSessionHas('error');

        $project->refresh();
        $this->assertSame('dibuka', $project->status);
        $this->assertSame('disetujui', $project->client_request_status);
    }
}
