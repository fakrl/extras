<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PersenCocokTest extends TestCase
{
    use RefreshDatabase;

    private function aplikasi(array $tagPeran, array $tagExtras, bool $pakaiKelas = true): ProjectApplication
    {
        $project = CastingProject::factory()->create();
        $kelas = $project->classes()->create(['nama_kelas' => 'Mahasiswa', 'budget_client' => 100000, 'kuota_kelas' => 2]);
        $kelas->categories()->sync($tagPeran);
        $extras = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
        $extras->categories()->sync($tagExtras);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'casting_project_class_id' => $pakaiKelas ? $kelas->id : null,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'diajukan',
        ]);
    }

    public function test_dua_dari_tiga_tag_jadi_67_tanpa_query_ulang_saat_eager_load(): void
    {
        $tags = collect(['Dewasa', 'Mahasiswa', 'Naik motor', 'Berhijab'])
            ->map(fn ($n) => ExtrasCategory::create(['nama' => $n])->id)->all();
        $app = $this->aplikasi([$tags[0], $tags[1], $tags[2]], [$tags[0], $tags[1], $tags[3]]);

        $app = ProjectApplication::with('castingProjectClass.categories', 'extras.categories')->find($app->id);
        DB::enableQueryLog();
        $this->assertSame(67, $app->persenCocok());
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_peran_tanpa_tag_atau_tanpa_kelas_null(): void
    {
        $tag = ExtrasCategory::create(['nama' => 'Dewasa'])->id;

        $this->assertNull($this->aplikasi([], [$tag])->persenCocok());
        $this->assertNull($this->aplikasi([$tag], [$tag], false)->persenCocok());
        $this->assertSame(0, $this->aplikasi([$tag], [])->persenCocok());
    }
}
