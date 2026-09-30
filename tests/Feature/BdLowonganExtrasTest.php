<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BdLowonganExtrasTest extends TestCase
{
    use RefreshDatabase;

    private CastingProject $project;

    private ExtrasCategory $motor;

    private ExtrasCategory $dewasa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = CastingProject::factory()->create([
            'nama_produksi' => 'Proyek BD7',
            'kuota' => 20, 'share_token' => Str::random(32),
        ]);
        $this->project->shootingDates()->create(['tanggal' => now()->addDays(10), 'lokasi' => 'Gudang Rahasia Cibubur']);
        $kelas = $this->project->classes()->create([
            'nama_kelas' => 'Pemotor', 'kriteria' => 'Pria 20-30 th', 'budget_client' => 777777, 'kuota_kelas' => 5,
        ]);
        $this->dewasa = ExtrasCategory::create(['nama' => 'Dewasa muda']);
        $this->motor = ExtrasCategory::create(['nama' => 'Naik motor']);
        $kelas->categories()->attach([$this->dewasa->id, $this->motor->id]);

        foreach (['diajukan', 'lolos', 'ditolak'] as $status) {
            $profil = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
            $this->project->applications()->create([
                'extras_id' => $profil->id, 'casting_project_class_id' => $kelas->id, 'status_partisipasi' => $status,
            ]);
        }
    }

    private function extrasDenganTagMotor(): User
    {
        $user = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $user->id])->categories()->attach($this->motor->id);

        return $user;
    }

    public function test_index_dan_show_tampilkan_sisa_kuota_tag_dan_penanda_tag_milik_extras(): void
    {
        $user = $this->extrasDenganTagMotor();

        foreach (['/extras/lowongan', '/extras/lowongan/'.$this->project->id] as $url) {
            $this->actingAs($user)->get($url)->assertOk()
                ->assertSee('Sisa 3 dari 5')
                ->assertSee('Pria 20-30 th')
                ->assertSee('<span class="xtag">#Dewasa muda</span>', false)
                ->assertSee('#Naik motor<span class="sr-only"> (kamu punya)</span>', false)
                ->assertDontSee('777777')
                ->assertDontSee('777.777')
                ->assertDontSee('Gudang Rahasia Cibubur');
        }
    }

    public function test_publik_tampil_tag_tanpa_highlight_dan_tidak_bocor(): void
    {
        $this->get('/event/'.$this->project->share_token)->assertOk()
            ->assertSee('Sisa 3 dari 5')
            ->assertSee('#Naik motor')
            ->assertDontSee('kamu punya')
            ->assertDontSee('is-hit')
            ->assertDontSee('PH Rahasia Banget')
            ->assertDontSee('777777')
            ->assertDontSee('777.777')
            ->assertDontSee('Gudang Rahasia Cibubur');
    }

    public function test_sisa_kuota_minimal_nol(): void
    {
        $kelas = $this->project->classes()->withTerisi()->first();
        $kelas->kuota_kelas = 1;

        $this->assertSame(0, $kelas->sisaKuota());
    }
}
