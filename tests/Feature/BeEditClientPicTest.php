<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeEditClientPicTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nama_produksi' => 'Proyek Edit',
            'client_ph' => '',
            'deadline' => now()->addDays(14)->toDateString(),
            'kuota' => 10,
            'tanggal_shooting' => [now()->addDays(20)->toDateString()],
            'kelas' => [['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 2]],
        ], $overrides);
    }

    public function test_ganti_client_dan_pic_buat_assignment_log_dan_client_ph_ikut(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $adminBaru = User::factory()->create(['role' => 'admin', 'name' => 'Admin Baru']);
        $lama = User::factory()->create(['role' => 'client', 'name' => 'Client Lama', 'nama_perusahaan' => 'PH Lama']);
        $baru = User::factory()->create(['role' => 'client', 'name' => 'Client Baru', 'nama_perusahaan' => 'PH Baru']);
        $project = CastingProject::factory()->create(['client_id' => $lama->id, 'client_ph' => 'PH Lama']);
        $project->cdAssignments()->create(['cd_user_id' => $lama->id]);

        $this->actingAs($sa)->get(route('admin.projects.edit', $project))
            ->assertOk()->assertSee('data-cari-select="client_id"', false)->assertSee('client-baru-dialog');

        $this->actingAs($sa)->patch(route('admin.projects.update', $project), $this->payload([
            'admin_id' => $adminBaru->id, 'client_id' => $baru->id, 'client_ph' => 'PH Lama',
        ]))->assertRedirect(route('admin.projects.index'));

        $project->refresh();
        $this->assertSame([$adminBaru->id, $baru->id, 'PH Baru'], [$project->admin_id, $project->client_id, $project->client_ph]);
        $this->assertEqualsCanonicalizing([$lama->id, $baru->id], $project->cdAssignments()->pluck('cd_user_id')->all());
        $log = ActivityLog::where('action', 'UPDATE_PROJECT_PIC')->sole();
        $this->assertStringContainsString('Client Client Lama → Client Baru', $log->description);
        $this->assertStringContainsString('→ Admin Baru', $log->description);
    }

    public function test_proyek_tanpa_client_wajib_pilih_saat_edit_dan_muncul_di_badge_serta_perlu_tindakan(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = CastingProject::factory()->create(['client_id' => null, 'nama_produksi' => 'Proyek Yatim']);
        CastingProject::factory()->create(['client_id' => User::factory()->create(['role' => 'client'])->id, 'nama_produksi' => 'Proyek Lengkap']);

        $this->actingAs($sa)->patch(route('admin.projects.update', $project), $this->payload(['admin_id' => $project->admin_id]))
            ->assertSessionHasErrors(['client_id' => 'Pilih akun Client proyek ini dulu (proyek lama belum punya Client).']);

        $this->actingAs($sa)->get(route('super-admin.dashboard'))
            ->assertSee('Client belum diisi')->assertSee(route('admin.projects.index', ['tanpa_client' => 1]), false);

        $this->actingAs($sa)->get(route('admin.projects.index', ['tanpa_client' => 1]))
            ->assertSee('Proyek Yatim')->assertSee('Client belum diisi')->assertDontSee('Proyek Lengkap');
    }
}
