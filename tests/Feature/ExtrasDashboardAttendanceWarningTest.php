<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtrasDashboardAttendanceWarningTest extends TestCase
{
    use RefreshDatabase;

    private function extras(): User
    {
        $u = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $u->id]);

        return $u;
    }

    private function aplikasi(User $u): ProjectApplication
    {
        $p = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => 'Proyek', 'deadline' => now()->addDays(30), 'kuota' => 10, 'status' => 'dibuka',
        ]);

        return $p->applications()->create(['extras_id' => $u->extrasProfile->id, 'status_partisipasi' => 'dibatalkan']);
    }

    public function test_skor_kehadiran_belum_ada_data_saat_belum_pernah_absen(): void
    {
        $u = $this->extras();

        $html = $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Belum ada data')
            ->getContent();

        $awal = strpos($html, 'Skor Kehadiran');
        $kartu = substr($html, $awal, strpos($html, 'Kesiapan Profil', $awal) - $awal);
        $this->assertStringNotContainsString('100%', $kartu);
    }

    public function test_peringatan_batal_mendadak_tampil_saat_dua_kali_atau_lebih(): void
    {
        $u = $this->extras();
        foreach (range(1, 2) as $i) {
            $this->aplikasi($u)->cancellations()->create([
                'dibatalkan_oleh' => 'extras', 'alasan' => "Batal {$i}", 'is_mendadak' => true,
            ]);
        }

        $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Kamu tercatat 2 kali batal mendadak. Batal mendadak ke-3 menandai akunmu melanggar dan akunmu akan diblokir.');
    }

    public function test_peringatan_batal_mendadak_tidak_tampil_saat_kurang_dari_dua(): void
    {
        $u = $this->extras();
        $this->aplikasi($u)->cancellations()->create([
            'dibatalkan_oleh' => 'extras', 'alasan' => 'Batal 1', 'is_mendadak' => true,
        ]);

        $this->actingAs($u)->get(route('extras.dashboard'))->assertOk()
            ->assertDontSee('menandai akunmu melanggar');
    }
}
