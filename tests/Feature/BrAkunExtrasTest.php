<?php

namespace Tests\Feature;

use App\Exports\ExtrasRecapExport;
use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * BR.1: Kelola Akun ▸ Extras = Kelola Akun Extras + Rekap Extras (RF-51/RF-52).
 * Rekap "paling sering terpilih" & "paling sering batal mendadak" jadi pilihan urutan.
 */
class BrAkunExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function extras(string $username, array $attr = []): ExtrasProfile
    {
        return ExtrasProfile::factory()->for(User::factory()->state(['role' => 'extras', 'username' => $username]))->create($attr);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_sidebar_admin_kelola_akun_submenu_tanpa_rekap(): void
    {
        $this->actingAs($this->admin())->get(route('admin.akun.extras'))
            ->assertOk()
            ->assertSee('<details class="sidebar-dropdown" open>', false)
            ->assertSeeInOrder(['Dashboard', 'Proyek', 'Kelola Akun', 'Extras', 'Riwayat Kerja', 'Absensi Lapangan'], false)
            ->assertDontSee('Rekap Extras')
            ->assertDontSee('/admin/recap');
    }

    public function test_urut_paling_sering_terpilih(): void
    {
        $project = CastingProject::factory()->create();
        $sering = $this->extras('sering_dipilih');
        $this->extras('jarang_dipilih');
        ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $sering->id, 'status_partisipasi' => 'lolos']);

        $this->actingAs($this->admin())->get(route('admin.akun.extras', ['urut' => 'terpilih']))
            ->assertOk()
            ->assertSeeInOrder(['@sering_dipilih', '@jarang_dipilih'])
            ->assertSee('terpilih 1x')
            ->assertViewHas('extras', fn ($p) => $p->first()->id === $sering->user_id && $p->first()->extrasProfile->terpilih_count === 1);
    }

    public function test_urut_paling_sering_batal_mendadak(): void
    {
        $seringBatal = $this->extras('tukang_batal');
        $sesekali = $this->extras('sesekali_batal');
        $bersih = $this->extras('tidak_pernah');
        $batal = fn (ExtrasProfile $e, bool $mendadak = true, string $oleh = 'extras') => ProjectApplication::create([
            'casting_project_id' => CastingProject::factory()->create()->id, 'extras_id' => $e->id, 'status_partisipasi' => 'dibatalkan',
        ])->cancellations()->create(['dibatalkan_oleh' => $oleh, 'alasan' => 'x', 'is_mendadak' => $mendadak]);
        foreach (range(1, 3) as $_) {
            $batal($seringBatal);
        }
        $batal($sesekali);
        $batal($sesekali, false);
        $batal($bersih, true, 'admin');

        $this->actingAs($this->admin())->get(route('admin.akun.extras', ['urut' => 'batal']))
            ->assertOk()
            ->assertSee('3x batal mendadak')
            ->assertViewHas('extras', fn ($p) => $p->pluck('id')->take(2)->all() === [$seringBatal->user_id, $sesekali->user_id]
                && $p->firstWhere('id', $bersih->user_id)->extrasProfile->cancel_count === 0);
    }

    public function test_filter_dan_tampilan_daftar(): void
    {
        $tag = ExtrasCategory::create(['nama' => 'Remaja']);
        $fav = $this->extras('fav_remaja', ['apresiasi' => true, 'grade_saat_ini' => 'A']);
        $fav->categories()->attach($tag);
        $this->extras('bukan_fav')->categories()->attach($tag);
        $this->extras('lain', ['apresiasi' => true]);
        User::factory()->create(['role' => 'client', 'name' => 'Klien Tersembunyi']);
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.akun.extras', ['tag' => [$tag->id], 'favorit' => 1, 'grade' => 'A']))
            ->assertOk()
            ->assertViewHas('extras', fn ($p) => $p->pluck('id')->all() === [$fav->user_id])
            ->assertViewHas('daftar', false)
            ->assertSee('class="xgrid"', false)
            ->assertDontSee('Klien Tersembunyi');

        $this->actingAs($admin)->get(route('admin.akun.extras', ['tampil' => 'daftar', 'per' => 10]))
            ->assertOk()
            ->assertViewHas('daftar', true)
            ->assertViewHas('extras', fn ($p) => $p->perPage() === 10 && $p->total() === 3)
            ->assertSee('<input type="hidden" name="tampil" value="daftar">', false)
            ->assertSee('data-profil-modal', false)
            ->assertSee(route('admin.users.kategori', $fav->user_id), false)
            ->assertSee(route('admin.users.toggle-status', $fav->user_id), false)
            ->assertSee(route('admin.extras.favorit', $fav->user_id), false)
            ->assertDontSee('class="xgrid"', false);

        // per kartu (24) tidak berlaku di tampilan daftar → default tabel 10
        $this->actingAs($admin)->get(route('admin.akun.extras', ['tampil' => 'daftar', 'per' => 24]))
            ->assertViewHas('extras', fn ($p) => $p->perPage() === 10);
    }

    public function test_export_mengikuti_filter_aktif(): void
    {
        Excel::fake();
        $tag = ExtrasCategory::create(['nama' => 'Remaja']);
        $this->extras('masuk_export', ['rate_card' => 250000])->categories()->attach($tag);
        $this->extras('tidak_masuk');

        $this->actingAs($this->admin())->get(route('admin.akun.extras', ['tag' => [$tag->id]]))
            ->assertSee(route('admin.akun.extras.export', ['tag' => [$tag->id]]), false);

        $this->actingAs($this->admin())->get(route('admin.akun.extras.export', ['tag' => [$tag->id]]))->assertOk();

        Excel::assertDownloaded('rekap-extras-'.now()->format('Y-m-d').'.xlsx', function (ExtrasRecapExport $export) {
            $baris = $export->collection();

            return $baris->count() === 1 && $baris->first()['alias'] === 'masuk_export' && $baris->first()['rate_card'] == 250000
                && $export->headings() === ['Alias', 'Status', 'Jumlah Terpilih', 'Jumlah Pembatalan', 'Rate Card'];
        });
    }

    public function test_route_lama_redirect_dengan_query_setara(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/admin/users?q=dimas')->assertRedirect(route('admin.akun.extras', ['q' => 'dimas']));
        $this->actingAs($admin)->get('/admin/recap?kategori_id=7')->assertRedirect(route('admin.akun.extras', ['tag' => [7]]));
        $this->actingAs($admin)->get('/admin/recap/export?kategori_id=7')->assertRedirect(route('admin.akun.extras.export', ['tag' => [7]]));
    }

    public function test_halaman_tetap_ok_tanpa_extras(): void
    {
        $this->actingAs($this->admin())->get(route('admin.akun.extras'))
            ->assertOk()
            ->assertSee('Tidak ada Extras yang cocok.');
    }

    public static function bukanAdminProvider(): array
    {
        return [['korlap'], ['client'], ['extras']];
    }

    #[DataProvider('bukanAdminProvider')]
    public function test_role_selain_admin_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get(route('admin.akun.extras'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.akun.extras.export'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.recap.index'))->assertForbidden();
    }
}
