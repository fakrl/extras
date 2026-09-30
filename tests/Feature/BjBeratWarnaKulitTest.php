<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Keputusan Fakrul (BJ): berat badan + warna kulit jadi tag grup "Warna kulit".
 */
class BjBeratWarnaKulitTest extends TestCase
{
    use RefreshDatabase;

    private function profil(array $attr = []): ExtrasProfile
    {
        return ExtrasProfile::factory()->create(['user_id' => User::factory()->create(['role' => 'extras'])->id] + $attr);
    }

    public function test_warna_kulit_cuma_dari_tag_grup_warna_kulit(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        $this->assertSame('Sawo matang', ExtrasCategory::tagWarnaKulit('sawo MATANG')->nama);
        $this->assertSame('Gelap', ExtrasCategory::tagWarnaKulit('Hitam')->nama);

        $a = $this->profil();
        $this->assertNull($a->warnaKulit());
        $a->categories()->attach(ExtrasCategory::tagWarnaKulit('Hitam'));
        $this->assertSame('Gelap', $a->fresh()->warnaKulit());
    }

    public function test_form_berat_tanpa_warna_kulit_dan_simpan(): void
    {
        $p = $this->profil();
        $this->actingAs($p->user)->get(route('extras.profile.edit'))->assertOk()
            ->assertSee('name="berat_badan"', false)
            ->assertDontSee('name="warna_kulit"', false)
            ->assertSee('Ukuran Baju <span style="color: var(--text-muted); font-weight: 400;">(opsional)</span>', false);

        $base = ['nama_asli' => 'Nama', 'username' => 'user_'.$p->user_id];
        $this->actingAs($p->user)->put('/extras/profil', $base + ['berat_badan' => 300])->assertSessionHasErrors('berat_badan');
        $this->actingAs($p->user)->put('/extras/profil', $base + ['berat_badan' => 55])->assertSessionHasNoErrors();
        $this->assertSame(55, $p->fresh()->berat_badan);
    }

    public function test_publik_tanpa_berat_dan_warna_kulit_pemilik_tampil(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        $p = $this->profil(['berat_badan' => 57]);
        $p->categories()->attach(ExtrasCategory::where('nama', 'Sawo matang')->value('id'));
        $p->generateShareToken();

        $this->get(route('public.extras.profile', $p->fresh()->share_token))->assertOk()
            ->assertDontSee('57 kg')->assertDontSee('Berat badan')->assertDontSee('Sawo matang')->assertDontSee('Warna kulit');
        $this->actingAs($p->user)->get(route('extras.profile.show'))->assertSee('57 kg')->assertSee('Sawo matang');
    }

    public function test_greenlight_tanpa_select_warna_kulit_dan_chip_tag_warna(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        $client = User::factory()->create(['role' => 'client']);
        $project = CastingProject::factory()->create();
        $project->update(['client_id' => $client->id]);
        $kelas = $project->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 2]);
        $p = $this->profil();
        $gelap = ExtrasCategory::where('nama', 'Gelap')->firstOrFail();
        $p->categories()->attach($gelap->id);
        ProjectApplication::create(['casting_project_id' => $project->id, 'casting_project_class_id' => $kelas->id, 'extras_id' => $p->id, 'status_partisipasi' => 'diajukan_ke_cd']);

        $this->actingAs($client)->get(route('cd.reviews.show', $project))->assertOk()
            ->assertDontSee('filter-warna-kulit')
            ->assertSee('data-tag="'.$gelap->id.'"', false)
            ->assertSee('data-warna-kulit="Gelap"', false);
    }
}
