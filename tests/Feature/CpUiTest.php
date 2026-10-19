<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** SPEC CP: hierarki tombol, seksi <details> terbuka, persen kelengkapan profil, riwayat proyek di modal admin. */
class CpUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_persen_kelengkapan_profil_0_sebagian_dan_100(): void
    {
        $kosong = ExtrasProfile::factory()->create([
            'nama_asli' => null, 'usia' => null, 'gender' => null, 'tinggi_badan' => null, 'bahasa' => null,
        ]);
        $this->assertSame(0, $kosong->persenKelengkapan());

        $sebagian = ExtrasProfile::factory()->create([
            'foto_profil_path' => 'extras/x/foto/a.jpg', 'bahasa' => null,
        ]);
        // usia, gender, tinggi_badan, nama_asli dari factory sudah terisi + foto_profil_path di atas = 5 dari 10.
        $this->assertSame(50, $sebagian->persenKelengkapan());

        $lengkap = ExtrasProfile::factory()->create([
            'foto_profil_path' => 'extras/x/foto/a.jpg',
            'riwayat_pengalaman' => [['judul' => 'Figuran', 'tahun' => 2024]],
            'foto_tambahan' => [1 => 'extras/x/foto-tambahan/1.jpg', 2 => 'extras/x/foto-tambahan/2.jpg'],
        ]);
        $lengkap->user->update(['nomor_wa' => '081234567890']);
        $lengkap->categories()->attach(ExtrasCategory::create(['nama' => 'Dewasa'])->id);
        $this->assertSame(100, $lengkap->persenKelengkapan());
    }

    public function test_kepala_profil_tampilkan_persen_lengkap(): void
    {
        $profile = ExtrasProfile::factory()->create(['foto_profil_path' => 'extras/x/foto/a.jpg']);

        $this->actingAs($profile->user)->get(route('extras.profile.edit'))
            ->assertOk()
            ->assertSee('Profil '.$profile->fresh()->persenKelengkapan().'% lengkap');
    }

    public function test_dashboard_extras_sembunyikan_badge_persen_kalau_sudah_100(): void
    {
        $profile = ExtrasProfile::factory()->create([
            'foto_profil_path' => 'extras/x/foto/a.jpg',
            'riwayat_pengalaman' => [['judul' => 'Figuran', 'tahun' => 2024]],
            'foto_tambahan' => [1 => 'extras/x/foto-tambahan/1.jpg', 2 => 'extras/x/foto-tambahan/2.jpg'],
        ]);
        $profile->user->update(['nomor_wa' => '081234567890']);
        $profile->categories()->attach(ExtrasCategory::create(['nama' => 'Dewasa'])->id);
        $this->assertSame(100, $profile->fresh()->persenKelengkapan());

        $this->actingAs($profile->user)->get(route('extras.dashboard'))
            ->assertOk()->assertDontSee('% lengkap');
    }

    public function test_riwayat_proyek_muncul_di_modal_admin_tapi_tidak_di_profil_sendiri(): void
    {
        $profile = ExtrasProfile::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id, 'nama_produksi' => 'Proyek Silam']);
        $kelas = $project->classes()->create(['nama_kelas' => 'Ibu rumah tangga', 'kuota_kelas' => 3]);
        ProjectApplication::create([
            'casting_project_id' => $project->id, 'casting_project_class_id' => $kelas->id,
            'extras_id' => $profile->id, 'status_partisipasi' => 'selesai_produksi',
        ]);

        $html = $this->actingAs($admin)->get(route('admin.extras.profil', $profile->user).'?partial=1')->assertOk()->getContent();
        $this->assertStringContainsString('Proyek Silam', $html);
        $this->assertStringContainsString('Ibu rumah tangga', $html);
        $this->assertStringContainsString((new ProjectApplication(['status_partisipasi' => 'selesai_produksi']))->label(), $html);

        // Super Admin pakai modal yang sama, tidak error.
        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($sa)->get(route('admin.extras.profil', $profile->user).'?partial=1')->assertOk()
            ->assertSee('Proyek Silam');

        // Extras sendiri lihat profilnya: section riwayat proyek tidak tampil.
        $this->actingAs($profile->user)->get(route('extras.profile.show'))->assertOk()
            ->assertDontSee('Proyek yang pernah diikuti');
    }

    public function test_riwayat_proyek_kosong_tampil_pesan_singkat(): void
    {
        $profile = ExtrasProfile::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.extras.profil', $profile->user).'?partial=1')
            ->assertOk()->assertSee('Belum pernah ikut proyek.');
    }

    public function test_lineup_aksi_lain_satu_tombol_brand_sisanya_sekunder(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $kelas = $project->classes()->create(['nama_kelas' => 'Mahasiswa', 'kuota_kelas' => 3]);
        $extras = ExtrasProfile::factory()->create();
        $app = ProjectApplication::create([
            'casting_project_id' => $project->id, 'casting_project_class_id' => $kelas->id,
            'extras_id' => $extras->id, 'status_partisipasi' => 'nego_fee',
        ]);

        $html = $this->actingAs($admin)->get(route('admin.projects.applicants', $project))->assertOk()->getContent();
        preg_match('/<div class="xaksi">.*?<\/div>/s', $html, $m);
        $this->assertNotEmpty($m, 'Blok "Aksi lain" tidak ketemu di halaman.');

        $this->assertSame(1, substr_count($m[0], 'btn-brand'), 'Harus cuma 1 tombol brand (Lanjut Nego) di "Aksi lain".');
        $this->assertStringContainsString('Lanjut Nego', $m[0]);
    }
}
