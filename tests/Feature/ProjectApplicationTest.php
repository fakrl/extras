<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin, string $tanggalShooting): CastingProject
    {
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $project->shootingDates()->create(['tanggal' => $tanggalShooting]);

        return $project;
    }

    public function test_ajukan_ke_cd_dengan_bentrok_jadwal_tetap_lanjut_tapi_flag_true(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        $tanggalBentrok = now()->addDays(10)->toDateString();
        $projectA = $this->buatProyek($admin, $tanggalBentrok);
        $projectB = $this->buatProyek($admin, $tanggalBentrok);

        ProjectApplication::create([
            'casting_project_id' => $projectA->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);

        $applicationB = ProjectApplication::create([
            'casting_project_id' => $projectB->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);

        $adaBentrok = $applicationB->ajukanKeCd();

        $this->assertTrue($adaBentrok);
        $this->assertSame('diajukan_ke_cd', $applicationB->fresh()->status_partisipasi);
        $this->assertTrue($applicationB->fresh()->bentrok_jadwal_flag);
    }

    public function test_ajukan_ke_cd_tanpa_bentrok_flag_tetap_false(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        $projectA = $this->buatProyek($admin, now()->addDays(10)->toDateString());

        $application = ProjectApplication::create([
            'casting_project_id' => $projectA->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);

        $adaBentrok = $application->ajukanKeCd();

        $this->assertFalse($adaBentrok);
        $this->assertSame('diajukan_ke_cd', $application->fresh()->status_partisipasi);
        $this->assertFalse($application->fresh()->bentrok_jadwal_flag);
    }

    public function test_ajukan_ke_cd_selain_status_deal_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $project = $this->buatProyek($admin, now()->addDays(10)->toDateString());

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $this->expectException(\LogicException::class);

        $application->ajukanKeCd();
    }

    public function test_batalkan_status_deal_mencatat_cancellation_dan_ubah_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $project = $this->buatProyek($admin, now()->addDays(10)->toDateString());

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);

        $cancellation = $application->batalkan('admin', 'Klien mengubah kebutuhan talent');

        $this->assertSame('dibatalkan', $application->fresh()->status_partisipasi);
        $this->assertSame('admin', $cancellation->dibatalkan_oleh);
        $this->assertFalse($cancellation->is_mendadak);
        $this->assertDatabaseHas('cancellations', [
            'project_application_id' => $application->id,
            'dibatalkan_oleh' => 'admin',
        ]);
    }

    public function test_batalkan_selain_status_deal_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $project = $this->buatProyek($admin, now()->addDays(10)->toDateString());

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $this->expectException(\LogicException::class);

        $application->batalkan('extras', 'Berubah pikiran');
    }

    public function test_batalkan_mendadak_kurang_dari_h2_increment_cancel_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $project = $this->buatProyek($admin, now()->addDay()->toDateString());

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);

        $cancellation = $application->batalkan('extras', 'Ada acara keluarga mendadak');

        $this->assertTrue($cancellation->is_mendadak);
        $this->assertSame(1, $extras->fresh()->cancel_count);
    }

    public function test_batalkan_tidak_mendadak_tidak_increment_cancel_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);
        $project = $this->buatProyek($admin, now()->addDays(10)->toDateString());

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);

        $application->batalkan('admin', 'Reschedule client');

        $this->assertSame(0, $extras->fresh()->cancel_count);
    }

    public function test_tiga_kali_batalkan_mendadak_pada_proyek_berbeda_membuat_status_melanggar(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        foreach (range(1, 3) as $i) {
            $project = $this->buatProyek($admin, now()->addDay()->toDateString());
            $application = ProjectApplication::create([
                'casting_project_id' => $project->id,
                'extras_id' => $extras->id,
                'status_partisipasi' => 'deal',
            ]);
            $application->batalkan('extras', "Pembatalan ke-{$i}");
        }

        $this->assertSame(3, $extras->fresh()->cancel_count);
        $this->assertSame('melanggar', $extras->fresh()->status);
    }

    public function test_admin_batalkan_mendadak_3x_tidak_membuat_status_melanggar(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        foreach (range(1, 3) as $i) {
            $project = $this->buatProyek($admin, now()->addDay()->toDateString());
            $application = ProjectApplication::create([
                'casting_project_id' => $project->id,
                'extras_id' => $extras->id,
                'status_partisipasi' => 'deal',
            ]);
            $cancellation = $application->batalkan('admin', "Reschedule client ke-{$i}");

            $this->assertTrue($cancellation->is_mendadak);
        }

        $this->assertSame(0, $extras->fresh()->cancel_count);
        $this->assertNotSame('melanggar', $extras->fresh()->status);
    }
}
