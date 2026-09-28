<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AyKomunikasiTest extends TestCase
{
    use RefreshDatabase;

    private function buatLineup(User $admin, int $jumlah): array
    {
        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Lineup',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 10,
        ]);

        $apps = collect(range(1, $jumlah))->map(fn () => ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id])->id,
            'status_partisipasi' => 'diajukan',
        ]));

        return [$project, $apps];
    }

    public function test_bulk_tolak_wajib_alasan_dan_menolak_semua_terpilih(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$project, $apps] = $this->buatLineup($admin, 2);
        $url = route('admin.projects.applicants.bulk', $project);

        $this->actingAs($admin)->post($url, ['ids' => $apps->pluck('id')->all(), 'aksi' => 'tolak'])
            ->assertSessionHasErrors('alasan_tolak');
        $this->assertSame(0, ProjectApplication::where('status_partisipasi', 'ditolak')->count());

        $this->actingAs($admin)->post($url, [
            'ids' => $apps->pluck('id')->all(),
            'aksi' => 'tolak',
            'alasan_tolak' => 'Tinggi tidak sesuai.',
        ])->assertRedirect();

        $this->assertSame(2, ProjectApplication::where('status_partisipasi', 'ditolak')->where('alasan_tolak', 'Tinggi tidak sesuai.')->count());
    }

    public function test_bulk_grade_dan_filter_status_lineup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [$project, $apps] = $this->buatLineup($admin, 2);

        $this->actingAs($admin)->post(route('admin.projects.applicants.bulk', $project), [
            'ids' => [$apps[0]->id],
            'aksi' => 'grade',
            'grade' => 'A',
        ])->assertRedirect();

        $this->assertSame('direview_admin', $apps[0]->fresh()->status_partisipasi);
        $this->assertSame('A', $apps[0]->fresh()->grade);

        $response = $this->actingAs($admin)->get(route('admin.projects.applicants', [$project, 'status' => 'direview_admin']));
        $response->assertOk();
        $this->assertSame(1, $response->viewData('applicants')->total());
    }

    public function test_absensi_header_ringkas_dan_dropdown_proyek_aktif_saja(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap']);
        [$project, $apps] = $this->buatLineup($admin, 1);
        $apps[0]->update(['status_partisipasi' => 'lolos']);
        $project->shootingDates()->create(['tanggal' => today()]);

        $lama = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Lama Banget',
            'client_ph' => 'PH Test',
            'deadline' => now()->subMonths(2),
            'kuota' => 5,
        ]);
        $lama->shootingDates()->create(['tanggal' => today()->subMonth()]);

        $this->actingAs($korlap)->get(route('admin.attendance.index'))
            ->assertOk()
            ->assertSee('0/1 hadir · 1 menunggu')
            ->assertSee('id="app-'.$apps[0]->id.'"', false)
            ->assertDontSee('Proyek Lama Banget');

        $this->actingAs($korlap)->get(route('admin.attendance.index', ['project' => $lama->id]))
            ->assertOk()
            ->assertSee('Proyek Lama Banget');
    }

    public function test_single_grade_redirect_ke_kartu_kandidat(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        [, $apps] = $this->buatLineup($admin, 1);

        $this->actingAs($admin)
            ->from('/admin/lineup')
            ->patch(route('admin.applications.grade', $apps[0]), ['grade' => 'B'])
            ->assertRedirect('/admin/lineup#app-'.$apps[0]->id);
    }
}
