<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\CdProjectAssignment;
use App\Models\EventShootingDate;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JadwalModulTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyek(User $admin): CastingProject
    {
        return CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Jadwal Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);
    }

    private function buatTanggalShooting(CastingProject $project, ?string $tanggal = null): EventShootingDate
    {
        return EventShootingDate::create([
            'casting_project_id' => $project->id,
            'tanggal' => $tanggal ?? now()->addDays(5)->toDateString(),
        ]);
    }

    private function assignCd(CastingProject $project, User $cd): void
    {
        CdProjectAssignment::create([
            'casting_project_id' => $project->id,
            'cd_user_id' => $cd->id,
        ]);
    }

    public function test_cd_bisa_simpan_jadwal_proyek_yang_diassign(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);
        $this->assignCd($project, $cd);

        $response = $this->actingAs($cd)->post(route('cd.jadwal.store', $project), [
            'tanggal' => $tanggal->tanggal->format('Y-m-d'),
            'lokasi' => 'Studio Merdeka',
            'jam_mulai' => '07:00',
            'jam_selesai' => '18:00',
            'catatan' => 'Bawa kostum sendiri',
            'panggilan' => [
                ['nama' => 'Ibu-ibu', 'jam' => '06:30'],
                ['nama' => 'Bapak-bapak', 'jam' => '07:00'],
            ],
        ]);

        $response->assertRedirect(route('cd.jadwal.show', $project));
        $this->assertDatabaseHas('event_shooting_dates', [
            'casting_project_id' => $project->id,
            'lokasi' => 'Studio Merdeka',
        ]);
    }

    public function test_cd_tidak_bisa_simpan_jadwal_proyek_yang_bukan_miliknya(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cdLain = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);

        // cdLain TIDAK di-assign ke project ini
        $response = $this->actingAs($cdLain)->post(route('cd.jadwal.store', $project), [
            'tanggal' => $tanggal->tanggal->format('Y-m-d'),
            'lokasi' => 'Studio Curian',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('event_shooting_dates', ['lokasi' => 'Studio Curian']);
    }

    public function test_extras_bisa_lihat_jadwal_proyek_yang_diikuti(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);
        $tanggal->update(['lokasi' => 'Studio A', 'jam_mulai' => '08:00']);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
        ]);

        $response = $this->actingAs($extrasUser)->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertSee('Studio A');
    }

    public function test_extras_tidak_lihat_jadwal_proyek_yang_tidak_diikuti(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);
        $tanggal->update(['lokasi' => 'Studio Rahasia']);

        // Extras tidak punya application ke project ini
        $response = $this->actingAs($extrasUser)->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertDontSee('Studio Rahasia');
    }

    public function test_panggilan_tersimpan_sebagai_json_array_dan_bisa_diretrieve(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $cd = User::factory()->create(['role' => 'casting_director']);
        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);
        $this->assignCd($project, $cd);

        $this->actingAs($cd)->post(route('cd.jadwal.store', $project), [
            'tanggal' => $tanggal->tanggal->format('Y-m-d'),
            'panggilan' => [
                ['nama' => 'Ara (Faris)', 'jam' => '06:30'],
                ['nama' => 'Perawat Klinik', 'jam' => '18:30'],
            ],
        ]);

        $date = EventShootingDate::where('casting_project_id', $project->id)
            ->whereNotNull('panggilan')
            ->first();
        $this->assertIsArray($date->panggilan);
        $this->assertCount(2, $date->panggilan);
        $this->assertEquals('Ara (Faris)', $date->panggilan[0]['nama']);
        $this->assertEquals('18:30', $date->panggilan[1]['jam']);
    }
}
