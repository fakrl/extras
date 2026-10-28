<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SPEC CS.1/CS.2: callsheet per tanggal shooting - isi, larangan kebocoran
 * data (nomor WA/NIK/nama asli/rekening Extras, fee, budget, nama Client
 * disembunyikan), peringatan data kosong, akses dikelolaOleh/Korlap ditugaskan/SA,
 * dan ActivityLog CALLSHEET_EXPORT.
 */
class CallsheetTest extends TestCase
{
    use RefreshDatabase;

    private function buatProyekLengkap(): array
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin PIC']);
        $adminLain = User::factory()->create(['role' => 'admin', 'name' => 'Admin Lain']);
        $korlap = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Bertugas', 'nomor_wa' => '081200000001']);
        $korlapLain = User::factory()->create(['role' => 'korlap', 'name' => 'Korlap Lain']);
        $sa = User::factory()->create(['role' => 'super_admin']);
        $client = User::factory()->create(['role' => 'client', 'nama_perusahaan' => 'PT Rahasia Klien Sentosa']);
        $clientUser = User::factory()->create(['role' => 'client']);

        $project = CastingProject::factory()->create([
            'admin_id' => $admin->id,
            'client_id' => $client->id,
            'nama_produksi' => 'Proyek Callsheet Rahasia',
            'budget_total' => 987654321,
            'tampilkan_nama_client' => false,
            'status' => 'dibuka',
        ]);

        AdminProjectAssignment::create(['casting_project_id' => $project->id, 'user_id' => $korlap->id, 'assigned_by' => $admin->id]);

        $class = $project->classes()->create([
            'nama_kelas' => 'Figuran Kampus',
            'kuota_kelas' => 5,
            'budget_client' => 555555,
            'jam_callingan' => '06:00',
            'keterangan_scene' => 'Baju kampus kasual',
            'tipe_continuity' => 'free',
        ]);

        $shootingDate = $project->shootingDates()->create([
            'tanggal' => today(),
            'lokasi' => 'Kampus Rahasia Jakarta',
            'jam_mulai' => '07:00',
            'jam_selesai' => '17:00',
            'catatan' => 'Bawa kartu identitas.',
        ]);

        $buatExtras = function (string $username, string $nomorWa) {
            static $nikSeed = 100000;
            $user = User::factory()->create(['role' => 'extras', 'username' => $username, 'nomor_wa' => $nomorWa]);
            $profile = ExtrasProfile::create([
                'user_id' => $user->id,
                'nama_asli' => 'Nama Asli Rahasia '.$username,
                'nik' => '3171'.str_pad((string) (++$nikSeed), 12, '0', STR_PAD_LEFT),
                'rekening' => '998877'.random_int(1000, 9999),
            ]);

            return $profile;
        };

        $extrasKontrak = $buatExtras('talent_kontrak', '081211110001');
        $appKontrak = $project->applications()->create([
            'extras_id' => $extrasKontrak->id, 'casting_project_class_id' => $class->id,
            'status_partisipasi' => 'kontrak_ditandatangani', 'fee_final' => 750000,
        ]);

        $extrasLolos = $buatExtras('talent_lolos', '081211110002');
        $appLolos = $project->applications()->create([
            'extras_id' => $extrasLolos->id, 'casting_project_class_id' => $class->id,
            'status_partisipasi' => 'lolos', 'fee_final' => 700000,
            'jam_callingan_override' => '05:00',
        ]);

        $extrasDiajukan = $buatExtras('talent_diajukan_saja', '081211110003');
        $project->applications()->create([
            'extras_id' => $extrasDiajukan->id, 'casting_project_class_id' => $class->id,
            'status_partisipasi' => 'diajukan',
        ]);

        return compact('admin', 'adminLain', 'korlap', 'korlapLain', 'sa', 'client', 'clientUser', 'project', 'class', 'shootingDate', 'appKontrak', 'appLolos');
    }

    private function url(CastingProject $p, $tanggal): string
    {
        return route('admin.callsheet.show', [$p, $tanggal instanceof Carbon ? $tanggal->toDateString() : $tanggal]);
    }

    public function test_hanya_talent_lolos_dan_kontrak_ditandatangani_yang_tampil(): void
    {
        $d = $this->buatProyekLengkap();

        $response = $this->actingAs($d['admin'])->get($this->url($d['project'], $d['shootingDate']->tanggal));

        $response->assertOk();
        $response->assertSee('@talent_kontrak');
        $response->assertSee('@talent_lolos');
        $response->assertDontSee('@talent_diajukan_saja');
    }

    public function test_tidak_ada_data_sensitif_yang_bocor(): void
    {
        $d = $this->buatProyekLengkap();

        $response = $this->actingAs($d['admin'])->get($this->url($d['project'], $d['shootingDate']->tanggal));

        $response->assertOk();
        // Nama asli, NIK, rekening Extras
        $response->assertDontSee('Nama Asli Rahasia talent_kontrak');
        $response->assertDontSee('Nama Asli Rahasia talent_lolos');
        foreach ([$d['appKontrak']->extras, $d['appLolos']->extras] as $extras) {
            $extras->refresh();
            $response->assertDontSee($extras->nik);
            $response->assertDontSee($extras->rekening);
        }
        // Nomor WA Extras (bukan Korlap)
        $response->assertDontSee('081211110001');
        $response->assertDontSee('081211110002');
        // Fee & budget
        $response->assertDontSee('750.000');
        $response->assertDontSee('700.000');
        $response->assertDontSee('555555');
        $response->assertDontSee('555.555');
        $response->assertDontSee('987654321');
        $response->assertDontSee('987.654.321');
        // Nama client disembunyikan (tampilkan_nama_client = false)
        $response->assertDontSee('PT Rahasia Klien Sentosa');
        // Korlap (staf) BOLEH tampil
        $response->assertSee('Korlap Bertugas');
    }

    public function test_jam_kumpul_override_pendaftaran_diutamakan_dari_peran(): void
    {
        $d = $this->buatProyekLengkap();

        $response = $this->actingAs($d['admin'])->get($this->url($d['project'], $d['shootingDate']->tanggal));

        $response->assertOk();
        // Default peran (class) 06:00 tetap tampil di header peran.
        $response->assertSee('06:00');
        // Override 05:00 milik talent_lolos harus tetap kelihatan (bukan tertimpa default).
        $response->assertSee('05:00');
    }

    public function test_data_kosong_menampilkan_peringatan(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $project->classes()->create(['nama_kelas' => 'Umum', 'kuota_kelas' => 3]);
        $tanggal = $project->shootingDates()->create(['tanggal' => today()]);

        $response = $this->actingAs($admin)->get($this->url($project, $tanggal->tanggal));

        $response->assertOk();
        $response->assertSee('Lengkapi dulu');
        $response->assertSee('(belum diisi)');
    }

    public function test_akses_admin_pic_boleh(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['admin'])->get($this->url($d['project'], $d['shootingDate']->tanggal))->assertOk();
    }

    public function test_akses_admin_lain_ditolak(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['adminLain'])->get($this->url($d['project'], $d['shootingDate']->tanggal))->assertForbidden();
    }

    public function test_akses_korlap_ditugaskan_boleh(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['korlap'])->get($this->url($d['project'], $d['shootingDate']->tanggal))->assertOk();
    }

    /** CS.1.3: badge TTD & link kelola proyek cuma buat Admin/SA, bukan Korlap. */
    public function test_pratinjau_korlap_tidak_lihat_badge_ttd_dan_link_kelola(): void
    {
        $d = $this->buatProyekLengkap();
        $response = $this->actingAs($d['korlap'])->get($this->url($d['project'], $d['shootingDate']->tanggal));

        $response->assertOk();
        $response->assertDontSee('Kontrak belum TTD');
        // Bukan assertDontSee(route(...)) polos - URL callsheet sendiri (.../salin, .../pdf)
        // berawalan sama persis dengan admin.projects.show, jadi dicek sampai tanda kutip penutup.
        $response->assertDontSee('href="'.route('admin.projects.show', $d['project']).'"', false);
        $response->assertSee(route('admin.dashboard'), false);
    }

    public function test_akses_korlap_lain_ditolak(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['korlapLain'])->get($this->url($d['project'], $d['shootingDate']->tanggal))->assertForbidden();
    }

    public function test_akses_super_admin_boleh_tanpa_mode(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['sa'])->get($this->url($d['project'], $d['shootingDate']->tanggal))->assertOk();
    }

    public function test_akses_client_ditolak(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['clientUser'])->get($this->url($d['project'], $d['shootingDate']->tanggal))->assertForbidden();
    }

    public function test_akses_extras_ditolak(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['appKontrak']->extras->user)->get($this->url($d['project'], $d['shootingDate']->tanggal))->assertForbidden();
    }

    public function test_unduh_pdf_content_type_nama_file_dan_log(): void
    {
        $d = $this->buatProyekLengkap();

        $response = $this->actingAs($d['admin'])->get(route('admin.callsheet.pdf', [$d['project'], $d['shootingDate']->tanggal->toDateString()]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'attachment; filename=Callsheet-'.$d['project']->kode_proyek.'-'.$d['shootingDate']->tanggal->toDateString().'.pdf');

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'CALLSHEET_EXPORT',
            'subject_type' => CastingProject::class,
            'subject_id' => $d['project']->id,
        ]);
        $log = ActivityLog::where('action', 'CALLSHEET_EXPORT')->first();
        $this->assertSame('pdf', $log->properties['format']);
    }

    public function test_salin_wa_mencatat_activity_log(): void
    {
        $d = $this->buatProyekLengkap();

        $response = $this->actingAs($d['admin'])->post(route('admin.callsheet.salin', [$d['project'], $d['shootingDate']->tanggal->toDateString()]));

        $response->assertNoContent();
        $log = ActivityLog::where('action', 'CALLSHEET_EXPORT')->first();
        $this->assertSame('wa', $log->properties['format']);
    }

    public function test_tanggal_tidak_dikenal_404(): void
    {
        $d = $this->buatProyekLengkap();
        $this->actingAs($d['admin'])->get($this->url($d['project'], today()->addYears(5)))->assertNotFound();
    }
}
