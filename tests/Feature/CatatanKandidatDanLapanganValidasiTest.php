<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Validasi "catatan kandidat" Admin (apresiasi_catatan, ApplicantController::toggleApresiasi)
 * dan catatan lapangan Korlap/Admin (ApplicantController::tambahCatatan) - dua field
 * yang sama-sama sudah punya rule max:1000, belum pernah dites edge case-nya.
 */
class CatatanKandidatDanLapanganValidasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(string $status = 'lolos'): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Catatan',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
        ]);
    }

    public function test_apresiasi_catatan_boleh_kosong(): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.applications.apresiasi', $application), [])
            ->assertSessionDoesntHaveErrors('apresiasi_catatan');
    }

    public function test_apresiasi_catatan_terlalu_panjang_ditolak(): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.applications.apresiasi', $application), [
            'apresiasi_catatan' => str_repeat('a', 1001),
        ])->assertSessionHasErrors('apresiasi_catatan');
    }

    public function test_apresiasi_catatan_1000_karakter_diterima(): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.applications.apresiasi', $application), [
            'apresiasi_catatan' => str_repeat('a', 1000),
        ])->assertSessionDoesntHaveErrors('apresiasi_catatan');
    }

    public function test_catatan_lapangan_isi_wajib_diisi(): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.applications.catatan', $application), ['jenis' => 'catatan'])
            ->assertSessionHasErrors('isi');
        $this->assertSame(0, $application->fieldNotes()->count());
    }

    public function test_catatan_lapangan_terlalu_panjang_ditolak(): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'catatan', 'isi' => str_repeat('b', 1001),
        ])->assertSessionHasErrors('isi');
    }

    public function test_catatan_lapangan_jenis_harus_catatan_atau_sanksi(): void
    {
        $application = $this->buatAplikasi();
        $admin = $application->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'pujian', 'isi' => 'Bagus.',
        ])->assertSessionHasErrors('jenis');
    }

    public function test_korlap_tidak_bisa_catat_kandidat_yang_belum_lolos(): void
    {
        $application = $this->buatAplikasi('diajukan');
        $sa = User::factory()->create(['role' => 'super_admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        AdminProjectAssignment::create([
            'casting_project_id' => $application->casting_project_id,
            'user_id' => $korlap->id,
            'assigned_by' => $sa->id,
        ]);

        $this->actingAs($korlap)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'catatan', 'isi' => 'Datang telat.',
        ])->assertSessionHas('error');

        $this->assertSame(0, $application->fieldNotes()->count());
    }

    public function test_korlap_bisa_catat_kandidat_yang_sudah_lolos(): void
    {
        $application = $this->buatAplikasi('lolos');
        $sa = User::factory()->create(['role' => 'super_admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        AdminProjectAssignment::create([
            'casting_project_id' => $application->casting_project_id,
            'user_id' => $korlap->id,
            'assigned_by' => $sa->id,
        ]);

        $this->actingAs($korlap)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'catatan', 'isi' => 'Datang tepat waktu.',
        ])->assertSessionHas('status');

        $this->assertSame(1, $application->fieldNotes()->count());
    }
}
