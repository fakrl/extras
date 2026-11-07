<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\AdminRingkasan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** QA: baris baru "Perlu Tindakan" Admin untuk pelamar status diajukan/direview_admin, scoped per PIC. */
class PelamarBaruRingkasanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pelamar_baru_dihitung_dan_discoping_per_admin_pic(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lain = User::factory()->create(['role' => 'admin']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Pelamar Baru',
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
            'deadline' => today()->addDays(5),
            'kuota' => 5,
        ]);

        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'status_partisipasi' => 'diajukan',
        ]);
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'status_partisipasi' => 'direview_admin',
        ]);
        // Status lain tidak boleh ikut terhitung.
        ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'status_partisipasi' => 'deal',
        ]);

        $proyekLain = CastingProject::create([
            'admin_id' => $lain->id,
            'nama_produksi' => 'Proyek Admin Lain',
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
            'deadline' => today()->addDays(5),
            'kuota' => 5,
        ]);
        ProjectApplication::create([
            'casting_project_id' => $proyekLain->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'status_partisipasi' => 'diajukan',
        ]);

        $this->assertSame(2, AdminRingkasan::untuk($admin)['pelamar_baru']['jumlah']);
        $this->assertSame(1, AdminRingkasan::untuk($lain)['pelamar_baru']['jumlah']);
    }
}
