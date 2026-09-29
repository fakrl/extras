<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\ProjectExpense;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Services\KeuanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BgLabelKeluarTest extends TestCase
{
    use RefreshDatabase;

    public function test_keluar_dipisah_dibayar_dan_belum_dengan_label(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $p = CastingProject::factory()->create();
        $p->shootingDates()->create(['tanggal' => today()]);
        $kelas = $p->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 500000, 'kuota_kelas' => 4]);
        foreach ([[100000, 'ditransfer', now()], [200000, 'disengketakan', now()], [300000, 'belum_dibayar', null]] as [$fee, $status, $at]) {
            $app = ProjectApplication::create([
                'casting_project_id' => $p->id, 'extras_id' => ExtrasProfile::factory()->create()->id,
                'casting_project_class_id' => $kelas->id, 'status_partisipasi' => 'kontrak_ditandatangani', 'fee_final' => $fee,
            ]);
            Payment::create(['project_application_id' => $app->id, 'status' => $status, 'ditransfer_at' => $at]);
        }
        foreach ([[40000, 'sudah', now()], [50000, 'belum', null]] as [$nominal, $status, $at]) {
            $asg = $p->adminAssignments()->create(['user_id' => User::factory()->create(['role' => 'korlap'])->id, 'assigned_by' => $sa->id, 'status_log' => 'selesai']);
            StaffPayroll::create(['admin_project_assignment_id' => $asg->id, 'nominal_pokok' => $nominal, 'status_bayar' => $status, 'dibayar_at' => $at]);
        }
        ProjectExpense::create(['casting_project_id' => $p->id, 'label' => 'Konsumsi', 'nominal' => 7000, 'tanggal' => today(), 'created_by' => $sa->id]);

        $cf = app(KeuanganService::class)->cashflowProyek($p->fresh());
        $this->assertEqualsWithDelta(347000, $cf->keluar_dibayar, 0.01);
        $this->assertEqualsWithDelta(350000, $cf->keluar_belum, 0.01);
        $this->assertEqualsWithDelta($cf->total_keluar, $cf->keluar_dibayar + $cf->keluar_belum, 0.01);

        $tip = 'Dashboard cuma menghitung yang sudah dibayar dalam periode; di proyek dihitung semua kewajiban.';
        $dash = $this->actingAs($sa)->get(route('super-admin.dashboard'))->assertOk()->assertSee('Keluar (sudah dibayar)')->assertSee($tip);
        $this->assertEqualsWithDelta($cf->keluar_dibayar, $dash->viewData('uang')->total_keluar, 0.01);
        foreach ([route('admin.projects.index'), route('admin.projects.show', [$p, 'tab' => 'cashflow'])] as $url) {
            $this->get($url)->assertOk()->assertSee('Sudah dibayar Rp 347.000')->assertSee('Belum dibayar Rp 350.000')->assertSee('Rp 697.000')->assertSee($tip);
        }
    }
}
