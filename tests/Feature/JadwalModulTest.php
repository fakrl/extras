<?php

namespace Tests\Feature;

use App\Models\CastingProject;
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

    private function assignClient(CastingProject $project, User $klien): void
    {
        $project->update(['client_id' => $klien->id]);
    }

    public function test_client_bisa_simpan_jadwal_proyek_yang_diassign(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);
        $this->assignClient($project, $klien);

        $response = $this->actingAs($klien)->post(route('client.jadwal.store', $project), [
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

        $response->assertRedirect(route('client.jadwal.show', $project));
        $this->assertDatabaseHas('event_shooting_dates', [
            'casting_project_id' => $project->id,
            'lokasi' => 'Studio Merdeka',
        ]);
    }

    public function test_client_tidak_bisa_simpan_jadwal_proyek_yang_bukan_miliknya(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $klienLain = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);

        // klienLain TIDAK di-assign ke project ini
        $response = $this->actingAs($klienLain)->post(route('client.jadwal.store', $project), [
            'tanggal' => $tanggal->tanggal->format('Y-m-d'),
            'lokasi' => 'Studio Curian',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('event_shooting_dates', ['lokasi' => 'Studio Curian']);
    }

    public function test_extras_bisa_lihat_jadwal_proyek_yang_diikuti(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
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
        $admin = User::factory()->create(['role' => 'admin']);
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
        $admin = User::factory()->create(['role' => 'admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $project = $this->buatProyek($admin);
        $tanggal = $this->buatTanggalShooting($project);
        $this->assignClient($project, $klien);

        $this->actingAs($klien)->post(route('client.jadwal.store', $project), [
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
