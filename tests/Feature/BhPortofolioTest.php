<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BhPortofolioTest extends TestCase
{
    use RefreshDatabase;

    private function proyek(string $nama, int $hariShooting): CastingProject
    {
        $p = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id, 'nama_produksi' => $nama, 'client_id' => User::factory()->create(['role' => 'client', 'nama_perusahaan' => 'PH Rahasia '.$nama])->id,
            'share_token' => Str::random(32), 'deadline' => today()->addDays($hariShooting - 3), 'kuota' => 5, 'status' => 'ditutup', 'client_request_status' => 'disetujui',
        ]);
        $p->shootingDates()->create(['tanggal' => today()->addDays($hariShooting), 'lokasi' => 'Jakarta']);

        return $p;
    }

    public function test_proyek_belum_selesai_tidak_bisa_diset(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $p = $this->proyek('Proyek Mendatang', 5);

        $this->actingAs($admin)->get(route('admin.projects.show', $p))->assertOk()->assertDontSee('Portofolio di beranda');
        $this->actingAs($admin)->from('/x')->patch(route('admin.projects.portofolio', $p), ['tampil_portofolio' => 1])
            ->assertRedirect('/x')->assertSessionHas('error');
        $this->assertFalse($p->fresh()->tampil_portofolio);
        $this->actingAs(User::factory()->create(['role' => 'korlap']))->patch(route('admin.projects.portofolio', $p), ['tampil_portofolio' => 1])->assertForbidden();
    }

    public function test_tampil_hanya_kalau_dicentang_dan_client_disembunyikan_default(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin']);
        $a = $this->proyek('Film Alfa', -10);
        $b = $this->proyek('Film Beta', -20);

        $this->get('/')->assertOk()->assertDontSee('id="portofolio"', false)->assertDontSee('Film Alfa');

        $this->actingAs($admin)->get(route('admin.projects.show', $a))->assertOk()->assertSee('Portofolio di beranda');
        $this->actingAs($admin)->patch(route('admin.projects.portofolio', $a), [
            'tampil_portofolio' => 1, 'portofolio_judul' => 'Alfa Judul Publik', 'portofolio_jenis' => 'Iklan TV', 'portofolio_tahun' => 2025,
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('activity_logs', ['action' => 'UPDATE_PORTOFOLIO', 'subject_id' => $a->id]);
        $this->actingAs($admin)->patch(route('admin.projects.portofolio', $a), ['portofolio_tahun' => 1500])->assertSessionHasErrors('portofolio_tahun');

        auth()->logout();
        $this->get('/')->assertSee('Alfa Judul Publik')->assertSee('Iklan TV · 2025')
            ->assertDontSee('PH Rahasia')->assertDontSee('Film Beta')->assertDontSee(route('admin.projects.show', $a));

        $a->update(['tampilkan_nama_client' => true]);
        $this->get('/')->assertSee('PH Rahasia Film Alfa');
    }
}
