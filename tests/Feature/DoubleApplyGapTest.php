<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * QA: setelah Extras mendaftar, halaman extras/lowongan/{id} masih menampilkan form lamaran
 * lagi (tidak switch ke status pendaftaran) - gap UX. Tapi server tetap menolak baris duplikat.
 * Test ini membuktikan dua halnya, TANPA mengubah view (lihat laporan untuk detail).
 */
class DoubleApplyGapTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_lamaran_masih_tampil_setelah_daftar_tapi_server_cegah_baris_duplikat(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id, 'foto_profil_path' => 'x.jpg', 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        $project = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => 'Proyek Double Apply', 'deadline' => now()->addDays(5), 'kuota' => 10, 'status' => 'dibuka',
        ]);
        $kelas = $project->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 1, 'kuota_kelas' => 5]);

        // (1) Daftar pertama kali - berhasil.
        $this->actingAs($extras)->post(route('extras.projects.apply', $project), ['casting_project_class_id' => $kelas->id])
            ->assertRedirect();
        $this->assertSame(1, ProjectApplication::where('casting_project_id', $project->id)->where('extras_id', $extras->extrasProfile->id)->count());

        // (2) Buka lagi halaman lowongan - form lamaran MASIH tampil (gap UX, belum ada status check).
        $this->actingAs($extras)->get(route('extras.projects.show', $project))
            ->assertOk()
            ->assertSee('action="'.route('extras.projects.apply', $project).'"', false);

        // (3) Daftar lagi - server tolak, tidak ada baris kedua.
        $this->actingAs($extras)->post(route('extras.projects.apply', $project), ['casting_project_class_id' => $kelas->id])
            ->assertRedirect()
            ->assertSessionHas('status', 'Kamu sudah mendaftar ke proyek ini sebelumnya.');
        $this->assertSame(1, ProjectApplication::where('casting_project_id', $project->id)->where('extras_id', $extras->extrasProfile->id)->count());
    }
}
