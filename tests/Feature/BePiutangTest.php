<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\ProjectExpense;
use App\Models\User;
use App\Services\KeuanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BE.3: Masuk (lunas) · Piutang · Keluar · Saldo · Proyeksi, sama di dashboard, daftar, detail.
 */
class BePiutangTest extends TestCase
{
    use RefreshDatabase;

    public function test_piutang_dan_proyeksi_sama_di_dashboard_daftar_detail(): void
    {
        $this->travelTo('2026-06-15 10:00:00');
        $sa = User::factory()->create(['role' => 'super_admin']);

        $p = CastingProject::factory()->create(['client_request_status' => 'disetujui']);
        $p->shootingDates()->create(['tanggal' => '2026-06-20']);
        $kelas = $p->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 500000, 'kuota_kelas' => 4]);
        $p->invoices()->create(['nominal' => 1500000, 'status_bayar' => 'lunas', 'dibayar_at' => '2026-06-10 09:00:00']);
        $p->invoices()->create([]); // belum lunas, nominal null -> nilaiInvoice 2.000.000
        $app = ProjectApplication::create([
            'casting_project_id' => $p->id, 'extras_id' => ExtrasProfile::factory()->create()->id,
            'casting_project_class_id' => $kelas->id, 'status_partisipasi' => 'kontrak_ditandatangani', 'fee_final' => 600000,
        ]);
        Payment::create(['project_application_id' => $app->id, 'status' => 'ditransfer', 'ditransfer_at' => '2026-06-11 09:00:00']);
        ProjectExpense::create(['casting_project_id' => $p->id, 'label' => 'Konsumsi', 'nominal' => 400000, 'tanggal' => '2026-06-12', 'created_by' => $sa->id]);

        $luar = CastingProject::factory()->create(['client_request_status' => 'disetujui']);
        $luar->shootingDates()->create(['tanggal' => '2026-08-01']);
        $luar->invoices()->create(['nominal' => 999000]);
        CastingProject::factory()->create(['client_request_status' => 'disetujui'])
            ->classes()->create(['nama_kelas' => 'X', 'budget_client' => 777000, 'kuota_kelas' => 1]);

        $cf = app(KeuanganService::class)->cashflowProyek($p->fresh());
        $this->assertSame(1500000.0, $cf->total_masuk);
        $this->assertSame(2000000.0, $cf->piutang);
        $this->assertEqualsWithDelta(1000000, $cf->total_keluar, 0.01);
        $this->assertEqualsWithDelta(500000, $cf->saldo, 0.01);
        $this->assertEqualsWithDelta(2500000, $cf->proyeksi, 0.01);
        $this->assertSame(28.6, $cf->persen_terpakai);

        $dash = $this->actingAs($sa)->get(route('super-admin.dashboard'))->assertOk();
        $uang = $dash->viewData('uang');
        $daftar = $this->get(route('admin.projects.index', ['dari' => '2026-06-01', 'sampai' => '2026-06-30']))->assertOk();
        $baris = $daftar->viewData('cashflow')[$p->id];
        $detail = $this->get(route('admin.projects.show', [$p, 'tab' => 'cashflow']))->assertOk();

        foreach (['total_masuk', 'piutang', 'total_keluar', 'saldo', 'proyeksi'] as $k) {
            $this->assertEqualsWithDelta($cf->$k, $uang->$k, 0.01, "dashboard $k");
            $this->assertEqualsWithDelta($cf->$k, $baris->$k, 0.01, "daftar $k");
            $this->assertEqualsWithDelta($cf->$k, $detail->viewData('cashflow')->$k, 0.01, "detail $k");
        }
        $this->assertSame([$p->id], $daftar->viewData('projects')->pluck('id')->all());

        foreach ([$dash, $daftar, $detail] as $r) {
            $r->assertSee('Piutang')->assertSee('Rp 2.000.000')->assertSee('Proyeksi')->assertSee('Rp 2.500.000')
                ->assertSee('Saldo minus wajar kalau invoice belum dibayar — lihat Proyeksi.');
        }
    }

    public function test_baris_perkiraan_tanpa_invoice_bukan_piutang(): void
    {
        $p = CastingProject::factory()->create();
        $p->classes()->create(['nama_kelas' => 'A', 'budget_client' => 150000, 'kuota_kelas' => 4]);

        $cf = app(KeuanganService::class)->cashflowProyek($p);

        $this->assertSame(0.0, $cf->piutang);
        $this->assertSame(0.0, $cf->proyeksi);
        $this->assertEqualsWithDelta(600000, $cf->masuk->first()->nominal, 0.01);
    }
}
