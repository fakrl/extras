<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BUG QA: breakdown hover dashboard Admin dulu pakai status('buka_lowongan'/'proses_seleksi')
 * yang tidak pernah ada di enum casting_projects.status (cuma dibuka/ditutup) - selalu 0.
 * Diganti pakai CastingProject::diTahap(), sama persis dengan kartu Ekosistem Proyek SA.
 */
class AdminDashboardBreakdownTahapTest extends TestCase
{
    use RefreshDatabase;

    public function test_breakdown_lowongan_seleksi_sesuai_ditahap_dan_konsisten_dengan_tab_admin_projects(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 2 proyek tahap "mendatang" (disetujui, shooting di masa depan) - dikelola admin ini.
        for ($i = 0; $i < 2; $i++) {
            $project = CastingProject::create([
                'admin_id' => $admin->id, 'nama_produksi' => "Proyek Mendatang {$i}",
                'deadline' => now()->addDays(10), 'kuota' => 5, 'status' => 'dibuka',
                'client_request_status' => 'disetujui',
            ]);
            $project->shootingDates()->create(['tanggal' => today()->addDays(5)]);
        }

        // 1 proyek tahap "berjalan" (shooting mencakup hari ini) - dikelola admin ini.
        $berjalan = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'Proyek Berjalan',
            'deadline' => now()->addDays(10), 'kuota' => 5, 'status' => 'dibuka',
            'client_request_status' => 'disetujui',
        ]);
        $berjalan->shootingDates()->create(['tanggal' => today()->subDay()]);
        $berjalan->shootingDates()->create(['tanggal' => today()->addDay()]);

        // 1 proyek "mendatang" milik admin LAIN - tidak boleh ikut terhitung.
        $adminLain = User::factory()->create(['role' => 'admin']);
        $punyaLain = CastingProject::create([
            'admin_id' => $adminLain->id, 'nama_produksi' => 'Proyek Admin Lain',
            'deadline' => now()->addDays(10), 'kuota' => 5, 'status' => 'dibuka',
            'client_request_status' => 'disetujui',
        ]);
        $punyaLain->shootingDates()->create(['tanggal' => today()->addDays(5)]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
        $response->assertSee('Buka Lowongan & Seleksi', false);
        $response->assertSee('>2<', false);

        $this->assertSame(2, CastingProject::diTahap('mendatang')->dikelolaOleh($admin)->count());
        $this->assertSame(1, CastingProject::diTahap('berjalan')->dikelolaOleh($admin)->count());

        // Konsisten dengan tab /admin/projects (dikelolaOleh = scoping default $saring, tanpa ?semua=1).
        $tabMendatang = $this->actingAs($admin)->get(route('admin.projects.index', ['tahap' => 'mendatang']))
            ->viewData('projects');
        $this->assertSame(2, $tabMendatang->total());

        $tabBerjalan = $this->actingAs($admin)->get(route('admin.projects.index', ['tahap' => 'berjalan']))
            ->viewData('projects');
        $this->assertSame(1, $tabBerjalan->total());
    }
}
