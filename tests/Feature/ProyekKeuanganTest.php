<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\ProjectExpense;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Services\KeuanganService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProyekKeuanganTest extends TestCase
{
    use RefreshDatabase;

    private function proyekLengkap(): array
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin']);
        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Budi']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id, 'client_request_status' => 'disetujui']);
        $kelas = $project->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 1000000, 'kuota_kelas' => 2]);
        $project->shootingDates()->create(['tanggal' => today()->addDays(5)]);

        $project->invoices()->create(['nominal' => 2000000, 'status_bayar' => 'lunas', 'dibayar_at' => now()]);

        foreach ([[300000, 50000], [200000, 0]] as [$fee, $addon]) {
            $app = ProjectApplication::create([
                'casting_project_id' => $project->id,
                'extras_id' => ExtrasProfile::factory()->create()->id,
                'casting_project_class_id' => $kelas->id,
                'status_partisipasi' => 'kontrak_ditandatangani',
                'fee_final' => $fee,
            ]);
            $pay = Payment::create(['project_application_id' => $app->id, 'status' => 'ditransfer', 'ditransfer_at' => now()]);
            if ($addon) {
                $pay->addons()->create(['label' => 'Transport', 'nominal' => $addon, 'created_by' => $admin->id]);
            }
        }

        $assignment = $project->adminAssignments()->create(['user_id' => $korlap->id, 'assigned_by' => $sa->id, 'status_log' => 'selesai']);
        $payroll = StaffPayroll::create(['admin_project_assignment_id' => $assignment->id, 'nominal_pokok' => 250000]);

        ProjectExpense::create(['casting_project_id' => $project->id, 'label' => 'Konsumsi', 'nominal' => 100000, 'tanggal' => today(), 'created_by' => $admin->id]);

        return compact('sa', 'admin', 'project', 'payroll');
    }

    public function test_cashflow_proyek_saldo_dan_persen_benar(): void
    {
        ['project' => $project] = $this->proyekLengkap();

        $cf = app(KeuanganService::class)->cashflowProyek($project->fresh());

        $this->assertSame(2000000.0, $cf->total_masuk);
        $this->assertSame(0.0, $cf->piutang);
        $this->assertEqualsWithDelta(900000, $cf->total_keluar, 0.01); // 350rb + 200rb + 250rb + 100rb
        $this->assertEqualsWithDelta(1100000, $cf->saldo, 0.01);
        $this->assertSame(45.0, $cf->persen_terpakai);
        $this->assertCount(2, $cf->extras);
        $this->assertFalse($cf->staf->first()->lunas);
    }

    public function test_cashflow_tanpa_masuk_persen_null_dan_invoice_belum_pakai_nilai_live(): void
    {
        $project = CastingProject::factory()->create();
        $cf = app(KeuanganService::class)->cashflowProyek($project);
        $this->assertNull($cf->persen_terpakai);
        $this->assertSame(0.0, $cf->total_masuk);

        $project->classes()->create(['nama_kelas' => 'A', 'budget_client' => 150000, 'kuota_kelas' => 4]);
        $project->invoices()->create([]);
        $cf = app(KeuanganService::class)->cashflowProyek($project->fresh());
        $this->assertSame(0.0, $cf->total_masuk);
        $this->assertSame(600000.0, $cf->piutang);
    }

    public function test_ringkasan_periode_abaikan_transaksi_di_luar_periode(): void
    {
        ['project' => $project, 'payroll' => $payroll] = $this->proyekLengkap();
        $payroll->tandaiDibayar();
        ProjectExpense::create(['casting_project_id' => $project->id, 'label' => 'Lama', 'nominal' => 999000, 'tanggal' => today()->subMonths(3)]);
        $project->invoices()->create(['nominal' => 5000000, 'status_bayar' => 'lunas', 'dibayar_at' => now()->subMonths(3)]);

        $r = app(KeuanganService::class)->ringkasanPeriode(today()->startOfMonth(), today()->endOfMonth());

        $this->assertEqualsWithDelta(2000000, $r->total_masuk, 0.01);
        $this->assertEqualsWithDelta(900000, $r->total_keluar, 0.01);
        $this->assertEqualsWithDelta(1100000, $r->saldo, 0.01);
        $this->assertCount(1, $r->per_bulan);

        $r3 = app(KeuanganService::class)->ringkasanPeriode(today()->subMonths(3)->startOfMonth(), today()->endOfMonth());
        $this->assertCount(4, $r3->per_bulan);
        $this->assertEqualsWithDelta(7000000, $r3->total_masuk, 0.01);
        $this->assertEqualsWithDelta(5000000, $r3->per_bulan->first()->masuk, 0.01);
    }

    public function test_tandai_lunas_idempotent_dan_tercatat(): void
    {
        Carbon::setTestNow('2026-09-29 10:00:00');
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = CastingProject::factory()->create();

        $this->actingAs($sa)->patch(route('admin.projects.invoice-lunas', $project), ['nominal' => 1500000])
            ->assertSessionHas('status');
        $invoice = $project->invoices()->first();
        $this->assertTrue($invoice->isLunas());
        $this->assertEquals(1500000, $invoice->nominal);
        $this->assertNotNull($invoice->dibayar_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'INVOICE_PAID', 'user_id' => $sa->id]);

        $this->actingAs($sa)->patch(route('admin.projects.invoice-lunas', $project), ['nominal' => 1])
            ->assertSessionHas('error');
        $this->assertEquals(1500000, $invoice->fresh()->nominal);
        $this->assertSame(1, ActivityLog::where('action', 'INVOICE_PAID')->count());
    }

    public function test_biaya_lain_hapus_hanya_pembuat_atau_super_admin(): void
    {
        $adminA = User::factory()->create(['role' => 'admin']);
        $adminB = User::factory()->create(['role' => 'admin']);
        $sa = User::factory()->create(['role' => 'super_admin']);
        $project = CastingProject::factory()->create(['client_request_status' => 'disetujui']);

        $this->actingAs($adminA)->post(route('admin.projects.expenses.store', $project), ['label' => 'Transport', 'nominal' => 75000, 'tanggal' => today()->toDateString()])
            ->assertSessionHas('status');
        $biaya = ProjectExpense::firstOrFail();
        $this->assertSame($adminA->id, (int) $biaya->created_by);
        $this->assertDatabaseHas('activity_logs', ['action' => 'PROJECT_EXPENSE_ADDED']);

        $this->actingAs($adminB)->delete(route('admin.expenses.destroy', $biaya))->assertForbidden();
        $this->assertModelExists($biaya);

        $this->actingAs($sa)->delete(route('admin.expenses.destroy', $biaya))->assertSessionHas('status');
        $this->assertModelMissing($biaya);
        $this->assertDatabaseHas('activity_logs', ['action' => 'PROJECT_EXPENSE_DELETED', 'user_id' => $sa->id]);

        $milikA = ProjectExpense::create(['casting_project_id' => $project->id, 'label' => 'X', 'nominal' => 1, 'tanggal' => today(), 'created_by' => $adminA->id]);
        $this->actingAs($adminA)->delete(route('admin.expenses.destroy', $milikA))->assertSessionHas('status');
        $this->assertModelMissing($milikA);
    }

    public function test_biaya_ditolak_untuk_proyek_menunggu_acc(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['client_request_status' => 'menunggu_acc']);

        $this->actingAs($admin)->post(route('admin.projects.expenses.store', $project), ['label' => 'X', 'nominal' => 1000, 'tanggal' => today()->toDateString()])
            ->assertSessionHas('error');
        $this->assertSame(0, ProjectExpense::count());
    }

    private function payloadProyek(array $override = []): array
    {
        return array_merge([
            'nama_produksi' => 'Iklan Kopi',
            'deadline' => now()->addDays(7)->toDateString(),
            'kuota' => 5,
            'tanggal_shooting' => [now()->addDays(10)->toDateString()],
            'kelas' => [['nama_kelas' => 'Barista', 'budget_client' => 400000, 'kuota_kelas' => 3]],
        ], $override);
    }

    public function test_super_admin_buat_proyek_pilih_pic_dan_client(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin', 'name' => 'Fakhrul']);
        $admin = User::factory()->create(['role' => 'admin']);
        $client = User::factory()->create(['role' => 'client', 'name' => 'PT Kopi Nusantara']);

        $this->actingAs($sa)->post(route('admin.projects.store'), $this->payloadProyek(['admin_id' => $admin->id, 'client_id' => $client->id]))
            ->assertRedirect(route('admin.projects.index'));

        $p = CastingProject::where('nama_produksi', 'Iklan Kopi')->firstOrFail();
        $this->assertSame($admin->id, (int) $p->admin_id);
        $this->assertSame($client->id, (int) $p->client_id);
        $this->assertSame('PT Kopi Nusantara', $p->client_ph);
        $this->assertSame('dibuka', $p->status);
        $this->assertSame('disetujui', $p->client_request_status);
        $this->assertSame($client->id, $p->fresh()->client_id);
        $this->assertDatabaseHas('activity_logs', ['action' => 'CREATE_PROJECT', 'description' => "Proyek 'Iklan Kopi' dibuat oleh Fakhrul (Super Admin)"]);
    }

    public function test_validasi_role_pic_dan_client(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras']);
        $nonaktif = User::factory()->create(['role' => 'client', 'status' => 'nonaktif']);

        $this->actingAs($sa)->post(route('admin.projects.store'), $this->payloadProyek(['admin_id' => $extras->id, 'client_id' => $nonaktif->id]))
            ->assertSessionHasErrors(['admin_id', 'client_id']);
        $this->actingAs($sa)->post(route('admin.projects.store'), $this->payloadProyek(['client_ph' => 'PH']))
            ->assertSessionHasErrors(['admin_id', 'client_id']);
        $this->assertSame(0, CastingProject::count());
    }

    public function test_admin_buat_proyek_default_pic_dirinya(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Rina']);

        $client = User::factory()->create(['role' => 'client']);

        // D1: Client wajib
        $this->actingAs($admin)->post(route('admin.projects.store'), $this->payloadProyek(['client_ph' => 'PH Manual']))
            ->assertSessionHasErrors(['client_id']);

        $this->actingAs($admin)->post(route('admin.projects.store'), $this->payloadProyek(['client_ph' => 'PH Manual', 'client_id' => $client->id]))
            ->assertRedirect(route('admin.projects.index'));

        $p = CastingProject::firstOrFail();
        $this->assertSame($admin->id, (int) $p->admin_id);
        $this->assertSame($client->id, (int) $p->client_id);
        $this->assertSame('PH Manual', $p->client_ph);
        $this->assertDatabaseHas('activity_logs', ['description' => "Proyek 'Iklan Kopi' dibuat oleh Rina (Admin)"]);
    }

    public function test_tahap_proyek_dari_tanggal_shooting(): void
    {
        $buat = function (array $tanggal, array $attr = []) {
            $p = CastingProject::factory()->create($attr + ['client_request_status' => 'disetujui']);
            foreach ($tanggal as $t) {
                $p->shootingDates()->create(['tanggal' => $t]);
            }

            return $p->id;
        };
        $mendatang = $buat([today()->addDays(3)]);
        $berjalan = $buat([today()->subDay(), today()->addDay()]);
        $hariIni = $buat([today()]);
        $selesai = $buat([today()->subDays(5)]);
        $tanpaJadwal = $buat([]);
        $ditutup = $buat([], ['status' => 'ditutup']);
        $acc = $buat([today()->addDays(3)], ['client_request_status' => 'menunggu_acc']);

        $ids = fn ($t) => CastingProject::diTahap($t)->pluck('id')->sort()->values()->all();
        $this->assertSame([$acc], $ids('menunggu_acc'));
        $this->assertSame([$mendatang, $tanpaJadwal], $ids('mendatang'));
        $this->assertSame([$berjalan, $hariIni], $ids('berjalan'));
        $this->assertSame([$selesai, $ditutup], $ids('selesai'));

        foreach (CastingProject::with('shootingDates')->get() as $p) {
            $harap = collect(array_keys(CastingProject::TAHAP))->first(fn ($t) => in_array($p->id, $ids($t)));
            $this->assertSame($harap, $p->tahap(), "proyek {$p->id}");
        }
    }

    public function test_daftar_proyek_search_chip_dan_uang(): void
    {
        ['sa' => $sa, 'project' => $project] = $this->proyekLengkap();
        $project->update(['nama_produksi' => 'Film Senja']);
        CastingProject::factory()->create(['nama_produksi' => 'Iklan Lain']);

        $this->actingAs($sa)->get(route('admin.projects.index', ['q' => 'senja', 'tahap' => 'mendatang']))
            ->assertOk()
            ->assertSee('Film Senja')
            ->assertDontSee('Iklan Lain')
            ->assertSee('Rp 2.000.000')
            ->assertSee('Rp 900.000')
            ->assertSee('Rp 1.100.000');
    }

    public function test_detail_proyek_tiga_tab_dan_tandai_dibayar_honor_staf(): void
    {
        ['admin' => $admin, 'project' => $project, 'payroll' => $payroll] = $this->proyekLengkap();

        $this->actingAs($admin)->get(route('admin.projects.show', $project))->assertOk()->assertSee('Korlap Budi')->assertSee('Warga');
        $this->actingAs($admin)->get(route('admin.projects.show', [$project, 'tab' => 'pendaftar']))->assertOk()->assertSee('xcard', false);
        $this->actingAs($admin)->get(route('admin.projects.show', [$project, 'tab' => 'cashflow']))
            ->assertOk()
            ->assertSee('45,0%')
            ->assertSee('Konsumsi')
            ->assertSee(route('admin.payrolls.tandai-dibayar', $payroll), false);

        $this->actingAs($admin)->patch(route('admin.payrolls.tandai-dibayar', $payroll))->assertSessionHas('status');
        $this->assertTrue($payroll->fresh()->isDibayar());
        $this->actingAs($admin)->patch(route('admin.payrolls.tandai-dibayar', $payroll))->assertSessionHas('error');
    }

    public function test_rekap_margin_lama_redirect_ke_proyek_keuangan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/rekap-margin')->assertRedirect(route('admin.projects.index'));
        $this->actingAs($admin)->get('/super-admin/rekap-margin?tab=staf')->assertRedirect(route('admin.projects.index', ['bayar' => 'staf']));
        $this->actingAs($admin)->get('/admin/rekap-margin?tab=extras')->assertRedirect(route('admin.projects.index', ['bayar' => 'extras']));
    }

    #[DataProvider('roleDitolak')]
    public function test_client_extras_korlap_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $project = CastingProject::factory()->create();

        $this->actingAs($user)->get(route('admin.projects.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.projects.show', $project))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.projects.invoice-lunas', $project), ['nominal' => 1])->assertForbidden();
        $this->actingAs($user)->post(route('admin.projects.expenses.store', $project), ['label' => 'X', 'nominal' => 1, 'tanggal' => today()->toDateString()])->assertForbidden();
    }

    public static function roleDitolak(): array
    {
        return [['client'], ['extras'], ['korlap']];
    }
}
