<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaKategoriTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_idempotent_dan_isi_grup(): void
    {
        ExtrasCategory::create(['nama' => 'Dewasa']);
        $this->seed(ExtrasCategorySeeder::class);
        $this->seed(ExtrasCategorySeeder::class);

        $this->assertSame(count(collect(ExtrasCategory::GRUP)->flatten()), ExtrasCategory::count());
        $this->assertSame('Usia tampilan', ExtrasCategory::where('nama', 'Dewasa')->value('grup'));
        $this->assertSame(array_keys(ExtrasCategory::GRUP), ExtrasCategory::perGrup()->keys()->all());
        $this->assertSame('Anak-anak', ExtrasCategory::perGrup()['Usia tampilan']->first()->nama);
    }

    public function test_extras_pilih_tag_sendiri_dan_form_lain_tidak_menghapus(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        $user = User::factory()->create(['role' => 'extras']);
        $profile = ExtrasProfile::create(['user_id' => $user->id]);
        $ids = ExtrasCategory::take(2)->pluck('id')->all();
        $base = ['nama_asli' => 'Nama', 'username' => 'user_'.$user->id];

        $this->actingAs($user)->get(route('extras.profile.edit'))->assertOk()->assertSee('Tentang Kamu');

        $this->actingAs($user)->put('/extras/profil', $base + ['categories_present' => 1, 'categories' => $ids])->assertRedirect();
        $this->assertEqualsCanonicalizing($ids, $profile->categories()->pluck('id')->all());

        $this->actingAs($user)->put('/extras/profil', $base)->assertRedirect();
        $this->assertSame(2, $profile->categories()->count());

        $this->actingAs($user)->put('/extras/profil', $base + ['categories_present' => 1])->assertRedirect();
        $this->assertSame(0, $profile->categories()->count());

        $this->actingAs($user)->put('/extras/profil', $base + ['categories_present' => 1, 'categories' => [99999]])
            ->assertSessionHasErrors('categories.0');
    }

    public function test_tag_per_peran_saat_buat_dan_edit_proyek(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        [$a, $b, $c] = ExtrasCategory::take(3)->pluck('id')->all();
        $admin = User::factory()->create(['role' => 'admin']);
        $base = [
            'nama_produksi' => 'Proyek Tag',
            'client_ph' => 'PH',
            'deadline' => now()->addDays(7)->toDateString(),
            'kuota' => 5,
            'tanggal_shooting' => [now()->addDays(10)->toDateString()],
        ];

        $this->actingAs($admin)->get(route('admin.projects.create'))->assertOk()->assertSee('tag-chips-tpl');

        $this->actingAs($admin)->post(route('admin.projects.store'), $base + ['kelas' => [
            ['nama_kelas' => 'Mahasiswa', 'budget_client' => 100000, 'kuota_kelas' => 2, 'categories' => [$a, $b]],
            ['nama_kelas' => 'Warga', 'budget_client' => 100000, 'kuota_kelas' => 2],
        ]])->assertRedirect(route('admin.projects.index'));

        $project = CastingProject::where('nama_produksi', 'Proyek Tag')->firstOrFail();
        $kelas = $project->classes()->orderBy('id')->get();
        $this->assertEqualsCanonicalizing([$a, $b], $kelas[0]->categories()->pluck('id')->all());
        $this->assertSame(0, $kelas[1]->categories()->count());

        $this->actingAs($admin)->get(route('admin.projects.edit', $project))->assertOk();

        $this->actingAs($admin)->patch(route('admin.projects.update', $project), $base + ['kelas' => [
            ['id' => $kelas[0]->id, 'nama_kelas' => 'Mahasiswa', 'budget_client' => 100000, 'kuota_kelas' => 2, 'categories' => [$c]],
        ]])->assertRedirect(route('admin.projects.index'));

        $baru = $project->classes()->with('categories')->get();
        $this->assertCount(1, $baru);
        $this->assertSame([$c], $baru[0]->categories->pluck('id')->all());
    }
}
