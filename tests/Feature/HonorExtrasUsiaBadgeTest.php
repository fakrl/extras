<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HonorExtrasUsiaBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_badge_usia_tampil_untuk_honor_belum_dibayar_sejak_shooting_terakhir(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $kelas = $project->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 200000, 'kuota_kelas' => 2]);
        $project->shootingDates()->create(['tanggal' => today()->subDays(10)]);

        $appBelumDibayar = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'casting_project_class_id' => $kelas->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
            'fee_final' => 150000,
        ]);
        Payment::create(['project_application_id' => $appBelumDibayar->id, 'status' => 'belum_dibayar']);

        $appSudahDibayar = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => ExtrasProfile::factory()->create()->id,
            'casting_project_class_id' => $kelas->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
            'fee_final' => 100000,
        ]);
        Payment::create(['project_application_id' => $appSudahDibayar->id, 'status' => 'ditransfer', 'ditransfer_at' => now()]);

        $r = $this->actingAs($admin)->get(route('admin.projects.show', [$project, 'tab' => 'keuangan']))->assertOk();

        $r->assertSee('H+10');
        $this->assertMatchesRegularExpression('/H\+\d+(?!\.\d)/', $r->getContent());
        $this->assertDoesNotMatchRegularExpression('/H\+\d+\.\d+/', $r->getContent());
    }
}
