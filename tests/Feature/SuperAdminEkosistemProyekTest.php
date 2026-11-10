<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminEkosistemProyekTest extends TestCase
{
    use RefreshDatabase;

    private function proyek(array $attrs): CastingProject
    {
        $admin = User::factory()->create(['role' => 'admin']);

        return CastingProject::factory()->create(array_merge(['admin_id' => $admin->id], $attrs));
    }

    public function test_kartu_ekosistem_proyek_cocok_dengan_scope_di_tahap_dan_tidak_semuanya_nol(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        // Menunggu ACC (1)
        $this->proyek(['client_request_status' => 'menunggu_acc']);

        // Mendatang: disetujui, shooting di masa depan (2)
        foreach (range(1, 2) as $i) {
            $p = $this->proyek(['client_request_status' => 'disetujui']);
            $p->shootingDates()->create(['tanggal' => today()->addDays(5)]);
        }

        // Berjalan: disetujui, shooting hari ini (3)
        foreach (range(1, 3) as $i) {
            $p = $this->proyek(['client_request_status' => 'disetujui']);
            $p->shootingDates()->create(['tanggal' => today()]);
        }

        // Selesai: disetujui, shooting sudah lewat (4)
        foreach (range(1, 4) as $i) {
            $p = $this->proyek(['client_request_status' => 'disetujui']);
            $p->shootingDates()->create(['tanggal' => today()->subDays(5)]);
        }

        // Ditolak/batal (5)
        foreach (range(1, 5) as $i) {
            $this->proyek(['client_request_status' => 'ditolak']);
        }

        $expected = [
            'menunggu_acc' => CastingProject::diTahap('menunggu_acc')->count(),
            'mendatang' => CastingProject::diTahap('mendatang')->count(),
            'berjalan' => CastingProject::diTahap('berjalan')->count(),
            'selesai' => CastingProject::diTahap('selesai')->count(),
            'batal' => CastingProject::where('client_request_status', 'ditolak')->count(),
        ];

        foreach ($expected as $label => $n) {
            $this->assertGreaterThan(0, $n, "Tahap {$label} seharusnya tidak nol di data seed.");
        }

        $html = $this->actingAs($sa)->get(route('super-admin.dashboard'))->assertOk()->getContent();

        // Angka legend/popover kartu "Ekosistem Proyek" harus sama dengan hitungan diTahap() langsung.
        $this->assertStringContainsString('>'.$expected['menunggu_acc'].'</strong>', $this->potongPopover($html));
        $this->assertStringContainsString((string) $expected['berjalan'], $this->potongPopover($html));
        $this->assertStringContainsString((string) $expected['mendatang'], $this->potongPopover($html));
        $this->assertStringContainsString((string) $expected['selesai'], $this->potongPopover($html));
        $this->assertStringContainsString((string) $expected['batal'], $this->potongPopover($html));
    }

    private function potongPopover(string $html): string
    {
        $awal = strpos($html, 'Status Siklus Proyek');
        $akhir = strpos($html, '</div>', strpos($html, 'Keterisian Kursi Aktif'));

        return substr($html, $awal, $akhir - $awal);
    }

    public function test_target_dan_terisi_kuota_tidak_ikutkan_proyek_yang_sudah_selesai(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $extras->id, 'foto_profil_path' => 'x.jpg', 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        // Proyek "mendatang": disetujui, shooting di masa depan, kuota 5, 1 orang deal (ikut dihitung).
        $mendatang = $this->proyek(['client_request_status' => 'disetujui']);
        $mendatang->shootingDates()->create(['tanggal' => today()->addDays(5)]);
        $kelasMendatang = $mendatang->classes()->create(['nama_kelas' => 'Warga', 'kuota_kelas' => 5]);
        $mendatang->applications()->create(['extras_id' => $extras->extrasProfile->id, 'casting_project_class_id' => $kelasMendatang->id, 'status_partisipasi' => 'deal']);

        // Proyek "selesai": shooting sudah lewat, status kolom TETAP 'dibuka'/'ditutup' (bukan literal 'selesai'),
        // kuota 10, 1 orang deal - keduanya HARUS dikecualikan dari target/terisi kuota.
        $selesai = $this->proyek(['client_request_status' => 'disetujui']);
        $selesai->shootingDates()->create(['tanggal' => today()->subDays(5)]);
        $kelasSelesai = $selesai->classes()->create(['nama_kelas' => 'Warga', 'kuota_kelas' => 10]);
        $selesai->applications()->create(['extras_id' => $extras->extrasProfile->id, 'casting_project_class_id' => $kelasSelesai->id, 'status_partisipasi' => 'deal']);

        // Bukti bug lama: kolom `status` proyek selesai TIDAK PERNAH berisi literal 'selesai'/'dibatalkan',
        // jadi whereNotIn('status', ['selesai', 'dibatalkan']) lama tidak pernah bisa mengecualikan proyek ini.
        $this->assertNotSame('selesai', $selesai->fresh()->status);
        $this->assertNotSame('dibatalkan', $selesai->fresh()->status);
        $this->assertContains($selesai->fresh()->status, ['dibuka', 'ditutup']);
        $this->assertTrue(CastingProject::diTahap('selesai')->whereKey($selesai->id)->exists());

        $html = $this->actingAs($sa)->get(route('super-admin.dashboard'))->assertOk()->getContent();

        // 1 deal di proyek "mendatang" dari target 5 kuota di sana - 10 kuota & 1 deal
        // proyek "selesai" TIDAK ikut (kalau bug lama masih ada, akan jadi "2 / 15").
        $this->assertStringContainsString('<strong>1 / 5</strong>', $this->potongPopover($html));
    }
}
