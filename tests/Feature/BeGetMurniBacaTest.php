<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsAppNotification;
use App\Mail\KontrakSiapTtdMail;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** SPEC BE.1: GET murni baca, record dibuat di transisi status. */
class BeGetMurniBacaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $client;

    private CastingProject $project;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Mail::fake();
        Queue::fake();

        $this->admin = User::factory()->create(['role' => 'admin', 'nomor_wa' => '081200000001']);
        $this->client = User::factory()->create(['role' => 'client']);
        $this->project = CastingProject::create([
            'admin_id' => $this->admin->id, 'nama_produksi' => 'Proyek BE', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $this->project->update(['client_id' => $this->client->id]);
    }

    private function aplikasi(string $status = 'diajukan_ke_cd', ?string $nik = '3201010101010001'): ProjectApplication
    {
        $user = User::factory()->create(['role' => 'extras', 'nomor_wa' => '081200000002']);
        $extras = ExtrasProfile::create(['user_id' => $user->id, 'nama_asli' => 'Nama KTP']);
        if ($nik) {
            $extras->lengkapiKtp($nik, null);
        }

        return ProjectApplication::create([
            'casting_project_id' => $this->project->id, 'extras_id' => $extras->id,
            'status_partisipasi' => $status, 'fee_final' => 200000,
        ]);
    }

    private function hitung(): array
    {
        return collect(['contracts', 'payments', 'invoices', 'notifications', 'jobs'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
    }

    private function approve(array $ids): void
    {
        $this->actingAs($this->client)->post(route('cd.reviews.review'), [
            'application_ids' => $ids, 'keputusan' => 'approve', 'grade_cd' => 'A',
        ])->assertRedirect();
    }

    public function test_get_kontrak_pembayaran_invoice_tidak_mengubah_data(): void
    {
        $application = $this->aplikasi('lolos');
        $sebelum = $this->hitung();

        foreach ([$application->extras->user, $this->admin] as $user) {
            $this->actingAs($user)->get(route('contracts.show', $application))->assertOk()->assertSee('Kontrak belum tersedia');
            $this->actingAs($user)->get(route('contracts.download-pdf', $application))->assertNotFound();
            $this->actingAs($user)->get(route('payments.show', $application))->assertOk()->assertSee('belum tersedia');
        }
        $this->actingAs($this->admin)->get(route('invoices.show', $this->project))->assertOk()->assertSee('Invoice belum tersedia');
        $this->actingAs($this->client)->get(route('invoices.show', $this->project))->assertOk()->assertSee('Invoice belum tersedia')
            ->assertSee('Invoice dibuat otomatis setelah ada kandidat yang dipilih Client.');

        $this->assertSame($sebelum, $this->hitung());
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();
    }

    public function test_approve_single_bikin_kontrak_payment_invoice_dan_notif_kontrak_sekali(): void
    {
        $application = $this->aplikasi();

        $this->approve([$application->id]);

        $application->refresh();
        $this->assertSame('lolos', $application->status_partisipasi);
        $this->assertNotNull($application->contract);
        Storage::disk('local')->assertExists($application->contract->pdf_path);
        $this->assertSame('belum_dibayar', $application->payment->status);
        $this->assertNull($this->project->invoices()->sole()->nominal);
        Mail::assertQueued(KontrakSiapTtdMail::class, 2);
        Queue::assertPushed(SendWhatsAppNotification::class, 3);
        $this->assertSame(2, DB::table('notifications')->where('data->jenis', 'kontrak_siap_ttd')->where('data->email', 'terkirim')->count());
        $this->assertSame(2, DB::table('notifications')->where('data', 'like', '%Kontrak Siap%')->count());

        $sebelum = $this->hitung();
        $application->siapkanKontrakDanPembayaran();
        $application->extras->siapkanKontrakLolos();
        $this->actingAs($application->extras->user)->get(route('contracts.show', $application))->assertOk()->assertSee('Status tanda tangan');
        $this->assertSame($sebelum, $this->hitung());
        Mail::assertQueued(KontrakSiapTtdMail::class, 2);
    }

    public function test_approve_bulk_bikin_record_per_aplikasi_satu_invoice(): void
    {
        $a = $this->aplikasi();
        $b = $this->aplikasi(nik: '3201010101010002');

        $this->approve([$a->id, $b->id]);

        $this->assertSame(['contracts' => 2, 'payments' => 2, 'invoices' => 1], array_intersect_key($this->hitung(), array_flip(['contracts', 'payments', 'invoices'])));
        Mail::assertQueued(KontrakSiapTtdMail::class, 4);
    }

    public function test_approve_tanpa_nik_payment_ada_kontrak_menyusul_saat_lengkapi_nik(): void
    {
        $application = $this->aplikasi(nik: null);

        $this->approve([$application->id]);

        $application->refresh();
        $this->assertNotNull($application->payment);
        $this->assertNull($application->contract);
        Mail::assertNotQueued(KontrakSiapTtdMail::class);

        $this->actingAs($application->extras->user)
            ->post(route('extras.kontrak.simpan-ktp', $application), ['nik' => '3201010101010009'])
            ->assertRedirect(route('contracts.show', $application));

        $this->assertNotNull($application->fresh()->contract);
        Mail::assertQueued(KontrakSiapTtdMail::class, 2);
        $this->assertSame(1, DB::table('contracts')->count());
    }

    public function test_migration_backfill_idempoten_tanpa_notifikasi(): void
    {
        $lengkap = $this->aplikasi('lolos');
        $tanpaNik = $this->aplikasi('kontrak_ditandatangani', null);
        $this->aplikasi('selesai_produksi', '3201010101010003');
        $this->aplikasi('diajukan_ke_cd', '3201010101010004');

        $migration = require database_path('migrations/2026_09_29_400001_backfill_kontrak_pembayaran_invoice.php');
        $migration->up();
        $pertama = $this->hitung();
        $migration->up();

        $this->assertSame($pertama, $this->hitung());
        $this->assertSame(['contracts' => 2, 'payments' => 3, 'invoices' => 1, 'notifications' => 0, 'jobs' => 0], $pertama);
        Storage::disk('local')->assertExists($lengkap->fresh()->contract->pdf_path);
        $this->assertNull($tanpaNik->fresh()->contract);
        Mail::assertNothingQueued();
        Queue::assertNothingPushed();
    }
}
