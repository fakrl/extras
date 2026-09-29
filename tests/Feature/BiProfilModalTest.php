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
 * SPEC BI.1: popup profil (partial via XHR) + versi Client di Greenlight.
 */
class BiProfilModalTest extends TestCase
{
    use RefreshDatabase;

    private ExtrasProfile $profile;

    private CastingProject $project;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'extras', 'username' => 'dimas.rk', 'name' => 'Akun Dimas', 'email' => 'dimas@contoh.test']);
        $this->profile = ExtrasProfile::factory()->create([
            'user_id' => $user->id, 'nama_asli' => 'Dimas Rahasia Kusuma', 'usia' => 27, 'rate_card' => 350000,
            'grade_saat_ini' => 'A', 'video_profil_path' => 'extras/video/x.mp4',
            'tautan_tambahan' => [['label' => 'Instagram', 'url' => 'https://instagram.com/dimasrahasia']],
        ]);
        $this->profile->categories()->attach(ExtrasCategory::create(['nama' => 'Jawa', 'grup' => 'Tampilan/Look'])->id);
        $this->project = CastingProject::create(['admin_id' => User::factory()->create(['role' => 'admin'])->id, 'nama_produksi' => 'Proyek BI', 'client_ph' => 'PH', 'deadline' => now()->addWeek(), 'kuota' => 5]);
    }

    private function ajukan(string $status = 'diajukan_ke_cd'): ProjectApplication
    {
        return ProjectApplication::create(['casting_project_id' => $this->project->id, 'extras_id' => $this->profile->id, 'status_partisipasi' => $status]);
    }

    private function client(): User
    {
        $cd = User::factory()->create(['role' => 'client']);
        $this->project->cdAssignments()->create(['cd_user_id' => $cd->id]);

        return $cd;
    }

    public function test_admin_xhr_dapat_partial_tanpa_layout(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('admin.extras.profil', $this->profile->user);

        foreach ([$this->actingAs($admin)->get($url, ['X-Requested-With' => 'XMLHttpRequest']), $this->actingAs($admin)->get($url.'?partial=1')] as $res) {
            $res->assertOk()->assertDontSee('<html', false)->assertDontSee('sidebar', false)
                ->assertSee('data-username="dimas.rk"', false)->assertSee('Rp 350.000')->assertSee('Grade A');
        }

        $this->actingAs($admin)->get($url)->assertOk()->assertSee('<html', false)->assertSee('id="profil-modal"', false);
    }

    public function test_client_lihat_kandidat_proyeknya_tanpa_data_internal(): void
    {
        $this->ajukan();
        $cd = $this->client();
        $url = route('cd.extras.profil', $this->profile->user);

        $this->actingAs($cd)->get($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()
            ->assertDontSee('<html', false)
            ->assertSee('#Jawa')
            ->assertSee('27 tahun')
            ->assertSee(route('extras.media.video', $this->profile), false)
            ->assertDontSee('350.000')
            ->assertDontSee('Tarif')
            ->assertDontSee('Grade')
            ->assertDontSee('Dimas Rahasia Kusuma')
            ->assertDontSee('Akun Dimas')
            ->assertDontSee('dimas@contoh.test')
            ->assertDontSee('instagram.com/dimasrahasia')
            ->assertDontSee('Ajak casting lewat JBTB');

        $this->actingAs($cd)->get($url)->assertOk()->assertSee('<html', false)
            ->assertDontSee('350.000')->assertDontSee('Dimas Rahasia Kusuma')->assertDontSee('Akun Dimas');
    }

    public function test_client_ditolak_untuk_extras_di_luar_proyeknya(): void
    {
        $url = route('cd.extras.profil', $this->profile->user);

        $this->ajukan();
        $this->actingAs(User::factory()->create(['role' => 'client']))->get($url)->assertForbidden();

        ProjectApplication::query()->update(['status_partisipasi' => 'deal']);
        $this->actingAs($this->client())->get($url)->assertForbidden();
    }

    public function test_markup_pemicu_popup_di_akun_lineup_greenlight(): void
    {
        $app = $this->ajukan();

        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->get(route('super-admin.akun.index', ['role' => 'extras']))
            ->assertOk()->assertSee('data-profil-modal', false)->assertSee('data-aksi-dialog="kelola-'.$this->profile->user_id.'"', false);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.projects.applicants', [$this->project, 'tab' => 'cd']))
            ->assertOk()->assertSee('data-profil-modal', false)->assertSee('data-aksi-dialog="detail-'.$app->id.'"', false);

        $this->actingAs($this->client())->get(route('cd.reviews.show', $this->project))
            ->assertOk()->assertSee('data-profil-modal', false)
            ->assertSee('href="'.route('cd.extras.profil', $this->profile->user_id).'"', false)
            ->assertSee('data-aksi-fungsi="bukaModalKandidat"', false);
    }
}
