<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\CdReview;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\ProjectAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BgGantiClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_ganti_client_cabut_akses_client_lama_review_tetap_ada(): void
    {
        Storage::fake('local');
        $sa = User::factory()->create(['role' => 'super_admin']);
        $lama = User::factory()->create(['role' => 'client', 'name' => 'Client Lama', 'username' => 'lama']);
        $baru = User::factory()->create(['role' => 'client', 'name' => 'Client Baru']);
        $project = CastingProject::factory()->create(['client_id' => $lama->id, 'nama_produksi' => 'Proyek Pindah']);
        $project->update(['client_id' => $lama->id]);
        $app = ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => ExtrasProfile::factory()->create()->id, 'status_partisipasi' => 'lolos']);
        $review = CdReview::create(['project_application_id' => $app->id, 'cd_id' => $lama->id, 'keputusan' => 'approve', 'grade_cd' => 'A']);
        ProjectAttachment::unggah($project, [UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf')], $sa);
        $file = $project->attachments()->first();

        $this->actingAs($sa)->get(route('admin.projects.edit', $project))
            ->assertOk()->assertSee('data-client-lama="'.$lama->id.'"', false)->assertSee('data-client-lama-nama="@lama"', false);

        $this->actingAs($sa)->patch(route('admin.projects.update', $project), [
            'nama_produksi' => 'Proyek Pindah',
            'admin_id' => $project->admin_id,
            'client_id' => $baru->id,
            'deadline' => now()->addDays(14)->toDateString(),
            'kuota' => 10,
            'tanggal_shooting' => [now()->addDays(20)->toDateString()],
            'kelas' => [['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 2]],
        ])->assertRedirect(route('admin.projects.index'));

        $this->assertSame($baru->id, $project->fresh()->client_id);
        $this->assertModelExists($review);
        $this->assertStringContainsString('Client Lama → Client Baru, akses Client lama dicabut', ActivityLog::where('action', 'UPDATE_PROJECT_PIC')->sole()->description);

        $jalur = [
            route('cd.reviews.show', $project),
            route('cd.jadwal.show', $project),
            route('invoices.show', $project),
            route('project-attachments.download', $file),
        ];
        foreach ($jalur as $url) {
            $this->actingAs($lama)->get($url)->assertForbidden();
            $this->actingAs($baru)->get($url)->assertOk();
        }
        $this->actingAs($lama)->get(route('invoices.index-client'))->assertOk()->assertDontSee('Proyek Pindah');
        $this->actingAs($lama)->get(route('cd.reviews.index'))->assertOk()->assertDontSee('Proyek Pindah');
    }
}
