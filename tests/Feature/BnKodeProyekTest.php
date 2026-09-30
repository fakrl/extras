<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Services\KeuanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BnKodeProyekTest extends TestCase
{
    use RefreshDatabase;

    private function proyek(array $attr = []): CastingProject
    {
        return CastingProject::factory()->create($attr);
    }

    public function test_format_kode_dari_tahun_dibuat_dan_id(): void
    {
        $p = $this->proyek();
        $p->created_at = '2026-03-01 10:00:00';

        $this->assertSame(sprintf('JBTB-2026-%03d', $p->id), $p->kode_proyek);
        $this->assertSame($p->kode_proyek, $p->kodeProyek);
        $this->assertSame(['tahun' => 2026, 'id' => 12], CastingProject::parseKode('jbtb-2026-012'));
        $this->assertSame(['tahun' => 2026, 'id' => 12], CastingProject::parseKode('2026-012'));
        $this->assertSame(['tahun' => null, 'id' => 12], CastingProject::parseKode('012'));
        $this->assertNull(CastingProject::parseKode('Iklan Kopi'));
    }

    public function test_live_search_proyek_bisa_cari_kode(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $target = $this->proyek(['nama_produksi' => 'Target Kode']);
        $lain = $this->proyek(['nama_produksi' => 'Proyek Lain']);
        $lama = $this->proyek(['nama_produksi' => 'Proyek Tahun Lalu']);
        $lama->forceFill(['created_at' => now()->subYear()])->save();
        $tahun = now()->year;

        foreach (["JBTB-{$tahun}-".sprintf('%03d', $target->id), "{$tahun}-".sprintf('%03d', $target->id), sprintf('%03d', $target->id)] as $q) {
            $ids = $this->actingAs($sa)->get(route('admin.projects.index', ['q' => $q]))->assertOk()
                ->viewData('projects')->pluck('id')->all();
            $this->assertSame([$target->id], $ids, $q);
        }

        $ids = $this->actingAs($sa)->get(route('admin.projects.index', ['q' => "JBTB-{$tahun}-".sprintf('%03d', $lama->id)]))
            ->viewData('projects')->pluck('id')->all();
        $this->assertSame([], $ids);

        $this->actingAs($sa)->get(route('admin.projects.index', ['q' => 'Lain']))
            ->assertSee($lain->kode_proyek)->assertDontSee('Target Kode');
        $this->actingAs($sa)->get(route('admin.projects.show', $target))->assertSee($target->kode_proyek);
    }

    public function test_kode_tampil_di_invoice_dan_kontrak_pdf(): void
    {
        $p = $this->proyek();
        $rincian = app(KeuanganService::class)->rincianInvoice($p);

        $this->assertStringContainsString($p->kode_proyek, view('invoices.pdf-template', ['castingProject' => $p, 'invoice' => $p->invoices()->create(), 'rincian' => $rincian])->render());

        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($sa)->get(route('invoices.show', $p))->assertOk()->assertSee($p->kode_proyek);

        $extras = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id, 'nama_asli' => 'Rina', 'nik' => '3201234567890011']);
        $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'lolos']);

        $this->assertStringContainsString($p->kode_proyek, view('contracts.pdf-template', ['application' => tap($app, fn ($a) => $a->contract()->create())->load('contract', 'extras', 'castingProject')])->render());
    }

    public function test_notifikasi_proyek_menyebut_kode(): void
    {
        $p = $this->proyek();
        $extras = ExtrasProfile::create(['user_id' => User::factory()->create(['role' => 'extras'])->id]);
        $app = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'diajukan']);

        $app->kirimKonfirmasiApply();

        $this->assertStringContainsString($p->kode_proyek, $extras->user->notifications()->first()->data['pesan']);
    }
}
