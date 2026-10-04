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
use App\Support\AdminRingkasan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function test_tab_keuangan_empat_blok_catatan_tanpa_total_lintas_blok(): void
    {
        ['admin' => $admin, 'project' => $project] = $this->proyekLengkap();

        $r = $this->actingAs($admin)->get(route('admin.projects.show', [$project, 'tab' => 'keuangan']))->assertOk();

        $r->assertSee('Invoice Client')->assertSee('Honor Extras')->assertSee('Honor Staf')->assertSee('Biaya Lain-lain')
            ->assertSee('2 dari 2 sudah ditransfer')->assertSee('Rp 350.000')->assertSee('Rp 200.000')->assertSee('Rp 250.000')->assertSee('Rp 100.000');
        foreach (['Saldo', 'Proyeksi', 'Piutang', 'Terpakai', 'Margin'] as $kata) {
            $r->assertDontSee($kata);
        }
    }

    public function test_invoice_nominal_manual_dengan_usulan_dari_rincian(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create();
        $project->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 150000, 'kuota_kelas' => 4]);
        $url = route('admin.projects.show', [$project, 'tab' => 'keuangan']);

        $this->actingAs($admin)->get($url)->assertOk()->assertSee('name="nominal" value="600000"', false);

        $this->patch(route('admin.projects.invoice-nominal', $project), ['nominal' => 750000])->assertSessionHas('status');
        $this->assertEquals(750000, $project->invoices()->first()->nominal);
        $this->assertDatabaseHas('activity_logs', ['action' => 'INVOICE_NOMINAL_SET', 'user_id' => $admin->id]);
        $this->get($url)->assertSee('name="nominal" value="750000"', false);

        $this->patch(route('admin.projects.invoice-nominal', $project), ['nominal' => -1])->assertSessionHasErrors('nominal');

        $this->patch(route('admin.projects.invoice-lunas', $project), ['nominal' => 750000]);
        $this->patch(route('admin.projects.invoice-nominal', $project), ['nominal' => 1])->assertSessionHas('error');
        $this->assertEquals(750000, $project->invoices()->first()->nominal);
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
        $this->assertSame('PT Kopi Nusantara', $p->namaClient());
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
        $this->actingAs($sa)->post(route('admin.projects.store'), $this->payloadProyek())
            ->assertSessionHasErrors(['admin_id', 'client_id']);
        $this->assertSame(0, CastingProject::count());
    }

    public function test_admin_buat_proyek_default_pic_dirinya(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Rina']);

        $client = User::factory()->create(['role' => 'client']);

        // D1: Client wajib
        $this->actingAs($admin)->post(route('admin.projects.store'), $this->payloadProyek())
            ->assertSessionHasErrors(['client_id']);

        $this->actingAs($admin)->post(route('admin.projects.store'), $this->payloadProyek(['client_id' => $client->id]))
            ->assertRedirect(route('admin.projects.index'));

        $p = CastingProject::firstOrFail();
        $this->assertSame($admin->id, (int) $p->admin_id);
        $this->assertSame($client->id, (int) $p->client_id);
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

    public function test_daftar_proyek_search_chip_tanpa_angka_uang(): void
    {
        ['sa' => $sa, 'project' => $project] = $this->proyekLengkap();
        $project->update(['nama_produksi' => 'Film Senja']);
        CastingProject::factory()->create(['nama_produksi' => 'Iklan Lain']);

        $this->actingAs($sa)->get(route('admin.projects.index', ['q' => 'senja', 'tahap' => 'mendatang']))
            ->assertOk()
            ->assertSee('Film Senja')
            ->assertDontSee('Iklan Lain')
            ->assertSee('Lihat detail')
            ->assertDontSee('Rp 2.000.000')->assertDontSee('Rp 900.000')
            ->assertDontSee('Piutang')->assertDontSee('Saldo')->assertDontSee('Proyeksi');
    }

    public function test_kartu_daftar_proyek_urgent_tanpa_baris_tahap_dan_lowongan(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        CastingProject::factory()->create(['nama_produksi' => 'Proyek Kilat', 'is_urgent' => true, 'status' => 'dibuka', 'client_request_status' => 'disetujui']);

        $r = $this->actingAs($sa)->get(route('admin.projects.index'))->assertOk();
        $r->assertSee('Proyek Kilat')->assertSee('badge badge-tolak">Urgent', false)
            ->assertDontSee('<span class="entity-card-row-label"', false)->assertDontSee('Pendaftar / kuota')
            ->assertDontSee('Lowongan ditutup')->assertDontSee('>dibuka<', false);
    }

    public function test_badge_lowongan_ditutup_hanya_untuk_proyek_mendatang(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $mendatang = CastingProject::factory()->create(['nama_produksi' => 'Akan Datang', 'status' => 'ditutup', 'client_request_status' => 'disetujui']);
        $mendatang->shootingDates()->create(['tanggal' => today()->addDays(20)]);
        $selesai = CastingProject::factory()->create(['nama_produksi' => 'Sudah Lewat', 'status' => 'ditutup', 'client_request_status' => 'disetujui']);
        $selesai->shootingDates()->create(['tanggal' => today()->subDays(20)]);

        $url = fn ($t) => route('admin.projects.index', ['tahap' => $t]);
        $this->actingAs($sa)->get($url('mendatang'))->assertSee('Lowongan ditutup');
        $this->get($url('selesai'))->assertSee('Sudah Lewat')->assertDontSee('Lowongan ditutup');
    }

    public function test_tab_tahap_filter_angka_dan_ditolak_masuk_menunggu_acc(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $buat = function (string $nama, string $req, ?int $hari = null) {
            $p = CastingProject::factory()->create(['nama_produksi' => $nama, 'client_request_status' => $req]);
            $hari !== null && $p->shootingDates()->create(['tanggal' => today()->addDays($hari)]);
        };
        $buat('P Acc', 'menunggu_acc');
        $buat('P Tolak', 'ditolak');
        $buat('P Depan1', 'disetujui', 5);
        $buat('P Depan2', 'disetujui', 9);
        $buat('P Jalan', 'disetujui', 0);
        $buat('P Lewat', 'disetujui', -9);

        $r = $this->actingAs($sa)->get(route('admin.projects.index'))->assertOk();
        $this->assertSame(['' => 6, 'menunggu_acc' => 2, 'mendatang' => 2, 'berjalan' => 1, 'selesai' => 1], $r->viewData('jumlahTahap')->all());
        $r->assertSee('Semua (6)')->assertSee('Menunggu ACC (2)')->assertSee('Mendatang (2)')->assertSee('Berjalan (1)')->assertSee('Selesai (1)');

        $this->get(route('admin.projects.index', ['tahap' => 'mendatang']))
            ->assertSee('P Depan1')->assertSee('P Depan2')->assertDontSee('P Jalan')->assertDontSee('P Lewat')->assertDontSee('P Acc');
        $acc = $this->get(route('admin.projects.index', ['tahap' => 'menunggu_acc']))
            ->assertSee('P Acc')->assertSee('P Tolak')->assertDontSee('P Depan1');
        $acc->assertSee('badge badge-tolak">Ditolak', false);

        $r = $this->get(route('admin.projects.index', ['tahap' => 'selesai', 'q' => 'Depan']))->assertDontSee('P Lewat');
        $this->assertSame(['' => 2, 'menunggu_acc' => 0, 'mendatang' => 2, 'berjalan' => 0, 'selesai' => 0], $r->viewData('jumlahTahap')->all());
        $r->assertSee('Tidak ada proyek yang sesuai filter.');
    }

    public function test_tahap_terjaga_di_pagination(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        CastingProject::factory()->count(14)->create(['client_request_status' => 'disetujui'])
            ->each(fn ($p) => $p->shootingDates()->create(['tanggal' => today()->addDays(7)]));

        $r = $this->actingAs($sa)->get(route('admin.projects.index', ['tahap' => 'mendatang', 'per' => 12]))->assertOk();
        $this->assertSame(14, $r->viewData('projects')->total());
        $this->assertStringContainsString('tahap=mendatang', $r->viewData('projects')->nextPageUrl());
    }

    public function test_honor_extras_tombol_transfer_atau_lihat_sesuai_status(): void
    {
        ['admin' => $admin, 'project' => $project] = $this->proyekLengkap();
        $url = route('admin.projects.show', [$project, 'tab' => 'keuangan']);
        $this->actingAs($admin)->get($url)->assertDontSee('>Transfer<', false)->assertSee('>Lihat<', false);

        $pay = $project->payments()->first();
        $pay->update(['status' => 'belum_dibayar', 'ditransfer_at' => null]);
        $href = route('payments.show', $pay->project_application_id);
        $this->get($url)->assertSee('href="'.$href.'" class="btn btn-sm btn-brand"', false)->assertSee('>Transfer<', false);

        $pay->projectApplication->update(['status_partisipasi' => 'lolos']);
        $this->get($url)->assertDontSee('>Transfer<', false);
    }

    public function test_perlu_tindakan_kartu_sama_dengan_ringkasan_dashboard_dan_tanpa_n_plus_1(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $buat = function (array $status) {
            $p = CastingProject::factory()->create();
            foreach ($status as $s) {
                ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => ExtrasProfile::factory()->create()->id, 'status_partisipasi' => $s]);
            }

            return $p;
        };
        $a = $buat(['diajukan', 'direview_admin', 'diajukan_ke_client', 'ditolak']);
        $b = $buat(['deal']);
        $c = $buat(['diajukan_ke_client']);

        $this->assertSame([$a->id => 2, $b->id => 1], AdminRingkasan::perluPerProyek([$a->id, $b->id, $c->id]));
        $this->assertSame(3, array_sum(array_column(AdminRingkasan::tahapan(), 'perlu')));

        $r = $this->actingAs($sa)->get(route('admin.projects.index'))->assertOk();
        $r->assertSee('Perlu tindakan (2)')->assertSee('Perlu tindakan (1)')
            ->assertSee(route('admin.projects.show', [$a, 'tab' => 'pendaftar']), false);

        $hitung = function () use ($sa) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($sa)->get(route('admin.projects.index'))->assertOk();

            return count(DB::getQueryLog());
        };
        $sebelum = $hitung();
        $buat(['diajukan', 'nego_fee']);
        $buat(['lolos']);
        $buat(['kontrak_ditandatangani']);
        $this->assertSame($sebelum, $hitung(), 'jumlah query tumbuh per proyek');
    }

    public function test_detail_proyek_empat_tab_dan_tandai_dibayar_honor_staf(): void
    {
        ['admin' => $admin, 'project' => $project, 'payroll' => $payroll] = $this->proyekLengkap();

        $this->actingAs($admin)->get(route('admin.projects.show', $project))->assertOk()->assertSee('Korlap Budi')->assertSee('Warga');
        $this->actingAs($admin)->get(route('admin.projects.show', [$project, 'tab' => 'pendaftar']))->assertOk()->assertSee('xcard', false);
        $this->actingAs($admin)->get(route('admin.projects.show', [$project, 'tab' => 'keuangan']))
            ->assertOk()
            ->assertSee('Konsumsi')
            ->assertSee(route('admin.payrolls.tandai-dibayar', $payroll), false);

        $this->actingAs($admin)->patch(route('admin.payrolls.tandai-dibayar', $payroll))->assertSessionHas('status');
        $this->assertTrue($payroll->fresh()->isDibayar());
        $this->actingAs($admin)->patch(route('admin.payrolls.tandai-dibayar', $payroll))->assertSessionHas('error');
    }

    #[DataProvider('roleDitolak')]
    public function test_client_extras_korlap_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $project = CastingProject::factory()->create();

        $this->actingAs($user)->get(route('admin.projects.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.projects.show', $project))->assertForbidden();
        $this->actingAs($user)->patch(route('admin.projects.invoice-lunas', $project), ['nominal' => 1])->assertForbidden();
        $this->actingAs($user)->patch(route('admin.projects.invoice-nominal', $project), ['nominal' => 1])->assertForbidden();
        $this->actingAs($user)->post(route('admin.projects.expenses.store', $project), ['label' => 'X', 'nominal' => 1, 'tanggal' => today()->toDateString()])->assertForbidden();
    }

    public static function roleDitolak(): array
    {
        return [['client'], ['extras'], ['korlap']];
    }
}
