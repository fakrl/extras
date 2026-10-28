<?php

namespace Tests\Feature;

use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC CS.3: kartu callingan Extras - muncul H-1/H untuk status lolos/kontrak_ditandatangani,
 * tidak muncul di luar rentang itu, tombol Absen Selfie hanya hari H.
 */
class CallinganCardTest extends TestCase
{
    use RefreshDatabase;

    private function buatPendaftaran(string $status, int $hariOffset): array
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Jaga', 'nomor_wa' => '081233330001']);
        $extrasUser = User::factory()->create(['role' => 'extras', 'username' => 'extras_callingan']);
        $extrasProfile = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::factory()->create(['admin_id' => $admin->id, 'nama_produksi' => 'Proyek Callingan']);
        AdminProjectAssignment::create(['casting_project_id' => $project->id, 'user_id' => $korlap->id, 'assigned_by' => $admin->id]);
        $class = $project->classes()->create(['nama_kelas' => 'Peran', 'kuota_kelas' => 5, 'jam_callingan' => '06:00', 'keterangan_scene' => 'Baju bebas']);
        $project->shootingDates()->create(['tanggal' => today()->addDays($hariOffset), 'lokasi' => 'Lokasi Shooting Callingan']);

        $project->applications()->create([
            'extras_id' => $extrasProfile->id, 'casting_project_class_id' => $class->id,
            'status_partisipasi' => $status,
        ]);

        return compact('extrasUser', 'project');
    }

    public function test_kartu_muncul_untuk_shooting_hari_ini(): void
    {
        $d = $this->buatPendaftaran('lolos', 0);

        $response = $this->actingAs($d['extrasUser'])->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertSee('Hari ini');
        $response->assertSee('data-dash="callingan"', false);
        $response->assertSee('06:00');
        $response->assertSee('Absen Selfie');
    }

    public function test_kartu_muncul_untuk_shooting_besok_tanpa_tombol_absen(): void
    {
        $d = $this->buatPendaftaran('kontrak_ditandatangani', 1);

        $response = $this->actingAs($d['extrasUser'])->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertSee('Besok');
        $response->assertSee('data-dash="callingan"', false);
        $response->assertDontSee('Absen Selfie');
    }

    public function test_kartu_tidak_muncul_h_minus_2(): void
    {
        $d = $this->buatPendaftaran('lolos', -2);

        $response = $this->actingAs($d['extrasUser'])->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-dash="callingan"', false);
    }

    public function test_kartu_tidak_muncul_h_plus_2(): void
    {
        $d = $this->buatPendaftaran('kontrak_ditandatangani', 2);

        $response = $this->actingAs($d['extrasUser'])->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-dash="callingan"', false);
    }

    public function test_kartu_tidak_muncul_h_minus_1(): void
    {
        $d = $this->buatPendaftaran('kontrak_ditandatangani', -1);

        $response = $this->actingAs($d['extrasUser'])->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-dash="callingan"', false);
    }

    public function test_kartu_tidak_muncul_untuk_status_selain_lolos_dan_kontrak(): void
    {
        $d = $this->buatPendaftaran('diajukan', 0);

        $response = $this->actingAs($d['extrasUser'])->get(route('extras.dashboard'));

        $response->assertOk();
        $response->assertDontSee('data-dash="callingan"', false);
    }
}
