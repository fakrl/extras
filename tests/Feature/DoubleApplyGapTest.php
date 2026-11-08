<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA: setelah Extras mendaftar, halaman extras/lowongan/{id} dulu masih menampilkan form
 * lamaran lagi (tidak switch ke status pendaftaran) - gap UX. Sekarang form disembunyikan
 * dan diganti kartu status kalau ada lamaran aktif. Server tetap menolak baris duplikat.
 */
class DoubleApplyGapTest extends TestCase
{
    use RefreshDatabase;

    private function proyek(): CastingProject
    {
        $project = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => 'Proyek Double Apply', 'deadline' => now()->addDays(5), 'kuota' => 10, 'status' => 'dibuka',
        ]);
        $project->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 1, 'kuota_kelas' => 5]);

        return $project;
    }

    public function test_sebelum_melamar_form_tetap_tampil(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id, 'foto_profil_path' => 'x.jpg', 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);
        $project = $this->proyek();

        $this->actingAs($extras)->get(route('extras.projects.show', $project))
            ->assertOk()
            ->assertSee('action="'.route('extras.projects.apply', $project).'"', false)
            ->assertDontSee('Kamu sudah mendaftar ke proyek ini');
    }

    public function test_setelah_daftar_form_disembunyikan_status_tampil_dan_server_cegah_duplikat(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id, 'foto_profil_path' => 'x.jpg', 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);
        $project = $this->proyek();
        $kelas = $project->classes->first();

        // (1) Daftar pertama kali - berhasil.
        $this->actingAs($extras)->post(route('extras.projects.apply', $project), ['casting_project_class_id' => $kelas->id])
            ->assertRedirect();
        $this->assertSame(1, ProjectApplication::where('casting_project_id', $project->id)->where('extras_id', $extras->extrasProfile->id)->count());

        // (2) Buka lagi halaman lowongan - form TIDAK tampil lagi, kartu status muncul.
        $this->actingAs($extras)->get(route('extras.projects.show', $project))
            ->assertOk()
            ->assertDontSee('action="'.route('extras.projects.apply', $project).'"', false)
            ->assertSee('Kamu sudah mendaftar ke proyek ini')
            ->assertSee('Warga');

        // (3) Daftar lagi lewat POST langsung - server tolak, tidak ada baris kedua.
        $this->actingAs($extras)->post(route('extras.projects.apply', $project), ['casting_project_class_id' => $kelas->id])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kamu sudah mendaftar ke proyek ini sebelumnya.');
        $this->assertSame(1, ProjectApplication::where('casting_project_id', $project->id)->where('extras_id', $extras->extrasProfile->id)->count());
    }
}
