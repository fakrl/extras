<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaKartuExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function lamaran(CastingProject $project, string $username, array $tagExtras, string $status = 'nego_fee'): ProjectApplication
    {
        $kelas = $project->classes()->firstOrCreate(['nama_kelas' => 'Mahasiswa kampus'], ['budget_client' => 100000, 'kuota_kelas' => 5]);
        $extras = ExtrasProfile::create([
            'user_id' => User::factory()->create(['role' => 'extras', 'username' => $username, 'name' => 'Nama Asli '.$username])->id,
            'nama_asli' => 'KTP '.$username,
        ]);
        $extras->categories()->sync($tagExtras);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'casting_project_class_id' => $kelas->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => $status,
            'grade' => 'A',
        ]);
    }

    private function tags(): array
    {
        return collect(['Dewasa', 'Mahasiswa', 'Naik motor'])->map(fn ($n) => ExtrasCategory::create(['nama' => $n])->id)->all();
    }

    public function test_lineup_kartu_ring_aksi_utama_dan_checkbox_bulk(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $t = $this->tags();
        $app = $this->lamaran($project, 'dimas_rk', [$t[0], $t[1]]);
        $app->castingProjectClass->categories()->sync($t);
        ExtrasProfile::find($app->extras_id)->applications()->create(['casting_project_id' => CastingProject::factory()->create()->id, 'status_partisipasi' => 'selesai_produksi']);

        $this->actingAs($admin)->get(route('admin.projects.applicants', $project))
            ->assertOk()
            ->assertSee('class="xcard-ring"', false)
            ->assertSee('67%')
            ->assertSee('Lanjut Nego')
            ->assertSee('1 proyek selesai')
            ->assertSee('form="bulk-form"', false)
            ->assertSee('id="detail-'.$app->id.'"', false)
            ->assertSee('ti-circle-check', false);
    }

    public function test_data_extras_admin_pakai_kartu_tanpa_aplikasi(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $extras = User::factory()->create(['role' => 'extras', 'username' => 'sari_mei']);
        ExtrasProfile::create(['user_id' => $extras->id]);
        User::factory()->create(['role' => 'extras', 'username' => null]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('@sari_mei')
            ->assertSee('Kelola')
            ->assertSee(route('admin.users.kategori', $extras), false)
            ->assertDontSee('class="xcard-ring"', false);
    }
}
