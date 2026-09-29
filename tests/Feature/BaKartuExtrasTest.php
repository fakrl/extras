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

    public function test_lineup_filter_tag_dan_urut_paling_cocok_lintas_halaman(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $t = $this->tags();
        $a33 = $this->lamaran($project, 'satu_tag', [$t[0]]);
        $a100 = $this->lamaran($project, 'semua_tag', $t);
        $a0 = $this->lamaran($project, 'tanpa_tag', []);
        $a33->castingProjectClass->categories()->sync($t);
        $url = fn (array $q) => route('admin.projects.applicants', [$project] + $q);

        $ids = fn ($res) => $res->viewData('applicants')->pluck('id')->all();
        $this->assertSame([$a100->id, $a33->id, $a0->id], $ids($this->actingAs($admin)->get($url(['urut' => 'cocok']))));

        $res = $this->actingAs($admin)->get($url(['tag' => [$t[1]]]));
        $this->assertSame([$a100->id], $ids($res));
        $res->assertSee('#Mahasiswa')->assertSee('Paling cocok')->assertSee('Menampilkan yang punya <strong>salah satu</strong> tag', false);
        $this->actingAs($admin)->get($url(['urut' => 'cocok']))->assertSee('diurutkan paling cocok');
    }

    public function test_greenlight_chip_tag_dan_data_cocok(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $project->cdAssignments()->create(['cd_user_id' => $client->id]);
        $t = $this->tags();
        $app = $this->lamaran($project, 'bagas_22', [$t[0], $t[1]], 'diajukan_ke_cd');
        $app->castingProjectClass->categories()->sync($t);

        $this->actingAs($client)->get(route('cd.reviews.show', $project))
            ->assertOk()
            ->assertSee('class="btn btn-sm gl-tag" data-tag="'.$t[2].'"', false)
            ->assertSee('data-cocok="67"', false)
            ->assertSee('Paling cocok')
            ->assertSee('Menampilkan yang punya <strong>salah satu</strong> tag', false);
    }

    public function test_greenlight_client_tanpa_grade_admin_nama_asli_dan_kontak(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $project->cdAssignments()->create(['cd_user_id' => $client->id]);
        $app = $this->lamaran($project, 'rahasia_22', [], 'diajukan_ke_cd');
        $email = $app->extras->user->email;

        $this->actingAs($client)->get(route('cd.reviews.show', $project))
            ->assertOk()
            ->assertSee('@rahasia_22')
            ->assertDontSee('data-grade-admin')
            ->assertDontSee('Rekomendasi Admin')
            ->assertDontSee('Nama Asli rahasia_22')
            ->assertDontSee('KTP rahasia_22')
            ->assertDontSee($email);
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
