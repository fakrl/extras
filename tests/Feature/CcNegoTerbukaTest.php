<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CcNegoTerbukaTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $o = []): array
    {
        return $o + [
            'nama_produksi' => 'Proyek CC', 'client_id' => User::factory()->create(['role' => 'client'])->id,
            'deadline' => now()->addDays(7)->toDateString(), 'kuota' => 5,
            'tanggal_shooting' => [now()->addDays(10)->toDateString()],
            'kelas' => [['nama_kelas' => 'Warga', 'budget_client' => 400000, 'kuota_kelas' => 3]],
        ];
    }

    private function aplikasi(bool $nego, string $status = 'nego_fee'): ProjectApplication
    {
        $extras = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
        $project = CastingProject::factory()->create(['nego_terbuka' => $nego, 'client_id' => User::factory()->create(['role' => 'client'])->id]);
        $app = ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $extras->id, 'status_partisipasi' => $status]);
        $app->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 150000, 'aksi' => 'tawar']);

        return $app;
    }

    public function test_default_dan_saklar_tersimpan_saat_buat_dan_edit(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->assertTrue(CastingProject::factory()->create()->fresh()->nego_terbuka);

        $this->actingAs($admin)->post('/admin/projects', $this->payload(['nego_terbuka' => 1]))->assertRedirect();
        $this->assertTrue(CastingProject::latest('id')->first()->nego_terbuka);

        $this->actingAs($admin)->post('/admin/projects', $this->payload(['nama_produksi' => 'Tetap']))->assertRedirect();
        $p = CastingProject::where('nama_produksi', 'Tetap')->first();
        $this->assertFalse($p->nego_terbuka);

        $this->actingAs($admin)->patch(route('admin.projects.update', $p), $this->payload(['admin_id' => $p->admin_id, 'client_id' => $p->client_id, 'nego_terbuka' => 1]))->assertRedirect();
        $this->assertTrue($p->fresh()->nego_terbuka);
    }

    public function test_edit_diabaikan_setelah_ada_penawaran(): void
    {
        $app = $this->aplikasi(true);
        $p = $app->castingProject;
        $admin = $p->admin;

        $this->actingAs($admin)->get(route('admin.projects.edit', $p))->assertSee('Sudah ada penawaran, tidak bisa diubah');
        $this->actingAs($admin)->patch(route('admin.projects.update', $p), $this->payload(['admin_id' => $admin->id, 'client_id' => $p->client_id]))->assertRedirect();

        $this->assertTrue($p->fresh()->nego_terbuka);
    }

    public function test_counter_extras_ditolak_pada_fee_tetap_dan_berhasil_pada_terbuka(): void
    {
        $tetap = $this->aplikasi(false);
        $this->actingAs($tetap->extras->user)->post(route('extras.negotiations.counter', $tetap), ['nominal' => 200000])
            ->assertSessionHas('error', 'Fee proyek ini tetap dan tidak bisa ditawar.');
        $this->assertSame(1, $tetap->feeNegotiations()->count());

        $this->actingAs($tetap->extras->user)->get(route('extras.negotiations.show', $tetap))
            ->assertSee('Penawaran ini final')->assertDontSee('Ajukan Counter');

        $buka = $this->aplikasi(true);
        $this->actingAs($buka->extras->user)->post(route('extras.negotiations.counter', $buka), ['nominal' => 200000]);
        $this->assertSame(2, $buka->feeNegotiations()->count());
    }

    public function test_counter_admin_ditolak_dan_terima_extras_jadi_deal_pada_fee_tetap(): void
    {
        $app = $this->aplikasi(false);
        $admin = $app->castingProject->admin;

        $this->actingAs($admin)->post(route('admin.negotiations.counter', $app), ['nominal' => 200000])
            ->assertSessionHas('error');
        $this->assertSame(1, $app->feeNegotiations()->count());
        $this->actingAs($admin)->get(route('admin.negotiations.show', $app))->assertSee('Fee tetap');

        $this->actingAs($app->extras->user)->post(route('extras.negotiations.terima', $app))->assertRedirect();
        $app->refresh();
        $this->assertSame(['deal', '150000.00'], [$app->status_partisipasi, number_format($app->fee_final, 2, '.', '')]);
    }

    public function test_label_di_lowongan_detail_event_dan_tidak_di_daftar_proyek_admin(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id]);
        $buka = CastingProject::factory()->create(['share_token' => 'tokbuka', 'nama_produksi' => 'Proyek Buka', 'nego_terbuka' => true, 'deadline' => now()->addDays(5)]);
        $tetap = CastingProject::factory()->create(['share_token' => 'toktetap', 'nama_produksi' => 'Proyek Tetap', 'nego_terbuka' => false, 'deadline' => now()->addDays(5)]);

        $this->actingAs($extras)->get(route('extras.projects.index'))->assertSee('Terbuka untuk nego fee')->assertSee('Fee tetap');
        $this->actingAs($extras)->get(route('extras.projects.show', $tetap))->assertSee('Fee tetap')->assertDontSee('Terbuka untuk nego fee');
        $this->actingAs($extras)->get(route('extras.projects.show', $buka))->assertSee('Terbuka untuk nego fee');
        auth()->logout();
        $this->get(route('public.event.show', $buka->share_token))->assertSee('Terbuka untuk nego fee');
        $this->get(route('public.event.show', $tetap->share_token))->assertSee('Fee tetap')->assertDontSee('Rp');

        $admin = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($admin)->get(route('admin.projects.index'))->assertDontSee('Fee tetap')->assertDontSee('Terbuka untuk nego fee');
        $this->actingAs($admin)->get(route('admin.projects.show', $tetap))->assertSee('Fee tetap');
    }
}
