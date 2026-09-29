<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC BH.1: satu layout profil editorial, 3 mode (pemilik/admin/publik), tembok visibilitas tetap.
 */
class BhProfilEditorialTest extends TestCase
{
    use RefreshDatabase;

    private ExtrasProfile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'extras', 'username' => 'dimas.rk', 'name' => 'Akun Dimas', 'nomor_wa' => '081299988877', 'email' => 'dimas@contoh.test']);
        $this->profile = ExtrasProfile::factory()->create([
            'user_id' => $user->id, 'nama_asli' => 'Dimas Rahasia Kusuma', 'nik' => '3174012345678901', 'rekening' => 'BCA 9988776655',
            'usia' => 27, 'gender' => 'pria', 'rate_card' => 350000, 'grade_saat_ini' => 'A', 'bahasa' => 'Indonesia, Jawa',
            'tautan_tambahan' => [['label' => 'Instagram', 'url' => 'https://instagram.com/dimasrahasia']],
        ]);
        $this->profile->generateShareToken();
        $this->profile->categories()->attach([
            ExtrasCategory::create(['nama' => 'Dewasa muda', 'grup' => 'Usia tampilan'])->id,
            ExtrasCategory::create(['nama' => 'Naik motor', 'grup' => 'Kemampuan'])->id,
            ExtrasCategory::create(['nama' => 'Jawa', 'grup' => 'Tampilan/Look'])->id,
        ]);
    }

    public function test_mode_publik_tanpa_data_internal(): void
    {
        $this->get(route('public.extras.profile', $this->profile->fresh()->share_token))->assertOk()
            ->assertSee('Profil Talent / #')
            ->assertSee('Ajak casting lewat JBTB')
            ->assertSee('25–29 tahun')
            ->assertDontSee('27 tahun')
            ->assertSee('Extras · Dewasa muda')
            ->assertSee('#Naik motor')
            ->assertDontSee('#Jawa')
            ->assertSee('Tersedia untuk Client &amp; Admin', false)
            ->assertDontSee('350.000')
            ->assertDontSee('Tarif')
            ->assertDontSee('Grade')
            ->assertDontSee('Dimas Rahasia Kusuma')
            ->assertDontSee('Akun Dimas')
            ->assertDontSee('3174012345678901')
            ->assertDontSee('9988776655')
            ->assertDontSee('081299988877')
            ->assertDontSee('dimas@contoh.test')
            ->assertDontSee('instagram.com/dimasrahasia')
            ->assertDontSee('Edit profil')
            ->assertDontSee(route('extras.media.foto', $this->profile), false);
    }

    public function test_mode_pemilik_ada_edit_tarif_grade(): void
    {
        $this->actingAs($this->profile->user)->get(route('extras.profile.show'))->assertOk()
            ->assertSee('Edit profil')
            ->assertSee('Bagikan')
            ->assertSee('Rp 350.000')
            ->assertSee('Grade A')
            ->assertSee('27 tahun')
            ->assertSee('#Jawa')
            ->assertSee('instagram.com/dimasrahasia')
            ->assertDontSee('Ajak casting lewat JBTB');
    }

    public function test_mode_admin_ada_tarif_grade_tanpa_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.extras.profil', $this->profile->user))->assertOk()
            ->assertSee('Rp 350.000')
            ->assertSee('Grade A')
            ->assertSee('Akun Dimas')
            ->assertSee('instagram.com/dimasrahasia')
            ->assertDontSee('Edit profil')
            ->assertDontSee('Bagikan')
            ->assertDontSee('Ajak casting lewat JBTB');
    }

    public function test_gallery_kosong_dan_badge_status(): void
    {
        $url = route('public.extras.profile', $this->profile->fresh()->share_token);
        $this->get($url)->assertSee('Belum ada foto')->assertSee('>Aktif<', false);

        $project = CastingProject::create(['admin_id' => User::factory()->create(['role' => 'admin'])->id, 'nama_produksi' => 'X', 'client_ph' => 'PH', 'deadline' => now()->addWeek(), 'kuota' => 5, 'status' => 'dibuka']);
        ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $this->profile->id, 'status_partisipasi' => 'deal']);
        $this->get($url)->assertSee('Sedang di proyek');

        $this->profile->user->update(['status' => 'nonaktif']);
        $this->get($url)->assertSee('Tidak aktif');
    }

    public function test_gallery_berisi_pakai_route_sesuai_mode(): void
    {
        $this->profile->photos()->create(['urutan' => 1, 'path' => 'extras/foto-tambahan/x.jpg']);
        $token = $this->profile->fresh()->share_token;

        $this->get(route('public.extras.profile', $token))
            ->assertSee(route('public.extras.foto-tambahan', [$token, 1]), false)
            ->assertDontSee('Belum ada foto');
        $this->actingAs($this->profile->user)->get(route('extras.profile.show'))
            ->assertSee(route('extras.media.foto-tambahan', [$this->profile, 1]), false);
    }
}
