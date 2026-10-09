<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\CastingProjectClass;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\AdminRingkasan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CE: Admin mengundang Extras ke proyek (status `diundang`), Extras terima/tolak, Admin batalkan. */
class CeUndangExtrasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'name' => 'Rina Admin']);
    }

    private function extras(string $username = 'budi_ce', array $profil = []): ExtrasProfile
    {
        $user = User::factory()->create(['role' => 'extras', 'username' => $username, 'name' => ucfirst($username), 'nomor_wa' => '081234567800']);

        return ExtrasProfile::factory()->create(['user_id' => $user->id] + $profil);
    }

    /** @param  int[]  $hari */
    private function proyek(string $nama = 'Film CE', array $hari = [10], int $kuotaKelas = 3, array $attr = []): CastingProject
    {
        $p = CastingProject::factory()->create($attr + [
            'admin_id' => $this->admin->id, 'nama_produksi' => $nama, 'kuota' => 10,
            'client_id' => User::factory()->create(['role' => 'client', 'name' => 'PH Rahasia'])->id,
        ]);
        $p->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 777000, 'kuota_kelas' => $kuotaKelas]);
        foreach ($hari as $h) {
            $p->shootingDates()->create(['tanggal' => now()->addDays($h)->toDateString()]);
        }

        return $p;
    }

    private function undang(ExtrasProfile $e, CastingProject $p, array $tambahan = [], ?User $oleh = null)
    {
        return $this->actingAs($oleh ?? $this->admin)->post(route('admin.undangan.store', $e->user), [
            'casting_project_id' => $p->id, 'casting_project_class_id' => $p->classes()->value('id'),
        ] + $tambahan);
    }

    private function baris(ExtrasProfile $e, CastingProject $p): ?ProjectApplication
    {
        return $p->applications()->where('extras_id', $e->id)->first();
    }

    private function diundang(ExtrasProfile $e, CastingProject $p): ProjectApplication
    {
        return ProjectApplication::create([
            'casting_project_id' => $p->id, 'extras_id' => $e->id, 'status_partisipasi' => 'diundang',
            'casting_project_class_id' => $p->classes()->value('id'), 'diundang_at' => now(),
        ]);
    }

    private function lamar(ExtrasProfile $e, CastingProject $p, string $status): ProjectApplication
    {
        return ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $e->id, 'status_partisipasi' => $status, 'casting_project_class_id' => $p->classes()->value('id')]);
    }

    public function test_undang_sukses_membuat_baris_notif_dan_log(): void
    {
        $e = $this->extras();
        $p = $this->proyek();

        $this->actingAs($this->admin)->get(route('admin.undangan.form', $e->user))->assertOk()->assertSee('Film CE')->assertSee('Warga');
        $this->undang($e, $p)->assertSessionHas('status');

        $a = $this->baris($e, $p);
        $this->assertSame('diundang', $a->status_partisipasi);
        $this->assertNotNull($a->diundang_at);
        $this->assertSame($p->classes()->value('id'), $a->casting_project_class_id);
        $this->assertFalse($a->bentrok_jadwal_flag);

        $n = $this->notifikasi($e->user_id, 'undangan_proyek');
        $this->assertCount(1, $n);
        $this->assertSame('/extras/lowongan/'.$p->id, $n[0]['url']);
        $this->assertStringContainsString('Rina Admin', $n[0]['pesan']);
        $this->assertStringContainsString('Film CE', $n[0]['pesan']);
        $this->assertStringContainsString('Warga', $n[0]['pesan']);
        $this->assertArrayNotHasKey('wa', $n[0]);
        $this->assertSame(1, ActivityLog::where('action', 'INVITE_EXTRAS')->count());
        $this->assertSame('Undang Extras ke proyek', ActivityLog::actionLabel('INVITE_EXTRAS'));
    }

    public function test_proyek_ditutup_boleh_diundang_tapi_yang_selesai_tidak(): void
    {
        $e = $this->extras();
        $ditutup = $this->proyek('Ditutup', [10], 3, ['status' => 'ditutup']);
        $this->undang($e, $ditutup)->assertSessionHas('status');
        $this->assertSame('diundang', $this->baris($e, $ditutup)->status_partisipasi);

        $selesai = $this->proyek('Selesai', [-5]);
        $this->undang($e, $selesai)->assertSessionHas('error');
        $this->assertNull($this->baris($e, $selesai));
        $this->actingAs($this->admin)->get(route('admin.undangan.form', $e->user))->assertSee('Ditutup')->assertDontSee('Selesai (');
    }

    public function test_ditolak_untuk_sudah_punya_baris_melanggar_nonaktif_kuota_dan_bentrok_pasti(): void
    {
        $p = $this->proyek();

        $ada = $this->extras('sudah_ada');
        $this->lamar($ada, $p, 'ditolak');
        $this->undang($ada, $p)->assertSessionHas('error', fn ($m) => str_contains($m, 'Ditolak'));
        $this->assertSame('ditolak', $this->baris($ada, $p)->status_partisipasi);

        $melanggar = $this->extras('si_melanggar', ['status' => 'melanggar']);
        $this->undang($melanggar, $p)->assertSessionHas('error');
        $this->assertNull($this->baris($melanggar, $p));

        $nonaktif = $this->extras('si_nonaktif');
        $nonaktif->user->update(['status' => 'nonaktif']);
        $this->undang($nonaktif, $p)->assertSessionHas('error');
        $this->assertNull($this->baris($nonaktif, $p));

        $kecil = $this->proyek('Kuota Kecil', [20], 1);
        $this->lamar($this->extras('pengisi'), $kecil, 'diajukan');
        $penuh = $this->extras('si_penuh');
        $this->undang($penuh, $kecil)->assertSessionHas('error', fn ($m) => str_contains($m, 'penuh'));
        $this->assertNull($this->baris($penuh, $kecil));

        $bentrok = $this->extras('si_bentrok');
        $this->lamar($bentrok, $this->proyek('Pasti', [10]), 'lolos');
        $this->undang($bentrok, $p)->assertSessionHas('error', fn ($m) => str_contains($m, 'Pasti'));
        $this->assertNull($this->baris($bentrok, $p));
    }

    public function test_peran_wajib_dan_harus_milik_proyek(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $lain = $this->proyek('Lain', [30])->classes()->value('id');

        $this->actingAs($this->admin)->post(route('admin.undangan.store', $e->user), ['casting_project_id' => $p->id])->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('admin.undangan.store', $e->user), ['casting_project_id' => $p->id, 'casting_project_class_id' => $lain])->assertSessionHas('error');
        $this->assertNull($this->baris($e, $p));
    }

    public function test_bentrok_masih_proses_butuh_konfirmasi_dan_tidak_menandai_flag(): void
    {
        $e = $this->extras();
        $lain = $this->lamar($e, $this->proyek('Proses', [10]), 'nego_fee');
        $p = $this->proyek('Baru', [10]);

        $this->undang($e, $p)->assertSessionHas('error', fn ($m) => str_contains($m, 'Proses'));
        $this->assertNull($this->baris($e, $p));

        $this->undang($e, $p, ['konfirmasi_bentrok' => 1])->assertSessionHas('status');
        $this->assertSame('diundang', $this->baris($e, $p)->status_partisipasi);
        $this->assertFalse($this->baris($e, $p)->bentrok_jadwal_flag);
        $this->assertFalse($lain->fresh()->bentrok_jadwal_flag);
    }

    public function test_diundang_tidak_menandai_bentrok_pendaftaran_lain_dan_tidak_dianggap_bentrok(): void
    {
        $e = $this->extras();
        $this->diundang($e, $this->proyek('Undangan', [10]));
        $p2 = $this->proyek('Lain', [10]);

        $this->assertTrue($e->pendaftaranBentrok(collect([now()->addDays(10)->toDateString()]))->isEmpty());
        $this->undang($e, $p2)->assertSessionHas('status');
    }

    public function test_terima_menjadi_diajukan_dan_cek_ulang_bentrok(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $a = $this->diundang($e, $p);

        $pasti = $this->lamar($e, $this->proyek('Jadi Pasti', [10]), 'lolos');
        $this->actingAs($e->user)->post(route('extras.undangan.terima', $a))->assertRedirect()->assertSessionHas('error');
        $this->assertSame('diundang', $a->fresh()->status_partisipasi);

        $pasti->update(['status_partisipasi' => 'nego_fee']);
        $this->actingAs($e->user)->post(route('extras.undangan.terima', $a))->assertRedirect()->assertSessionHas('konfirmasi_bentrok');
        $this->assertSame('diundang', $a->fresh()->status_partisipasi);

        $this->actingAs($e->user)->post(route('extras.undangan.terima', $a), ['konfirmasi_bentrok' => 1])->assertRedirect(route('extras.dashboard'));
        $this->assertSame('diajukan', $a->fresh()->status_partisipasi);
        $this->assertTrue($a->fresh()->bentrok_jadwal_flag);
        $this->assertNotNull($a->fresh()->diundang_at);
        $this->assertTrue($pasti->fresh()->bentrok_jadwal_flag);

        $this->actingAs($e->user)->post(route('extras.undangan.terima', $a))->assertSessionHas('error');
    }

    public function test_terima_tanpa_bentrok_dan_notif_admin_pic(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $a = $this->diundang($e, $p);

        $this->actingAs($e->user)->post(route('extras.undangan.terima', $a))->assertRedirect(route('extras.dashboard'));
        $this->assertSame('diajukan', $a->fresh()->status_partisipasi);
        $this->assertFalse($a->fresh()->bentrok_jadwal_flag);
        $this->assertNotifikasi($this->admin->id, 'undangan_balasan');
        $this->assertSame(1, ActivityLog::where('action', 'INVITE_ACCEPT')->count());
        $this->assertSame(0, $a->cancellations()->count());
    }

    public function test_tolak_menjadi_ditolak_tanpa_cancellations_dan_notif_admin(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $a = $this->diundang($e, $p);

        $this->actingAs($e->user)->post(route('extras.undangan.tolak', $a), ['alasan' => 'Sedang sibuk'])->assertRedirect(route('extras.dashboard'));
        $a->refresh();
        $this->assertSame('ditolak', $a->status_partisipasi);
        $this->assertSame('Menolak undangan: Sedang sibuk', $a->alasan_tolak);
        $this->assertSame(0, $a->cancellations()->count());
        $this->assertSame(0, $e->batalMendadak()->count());
        $this->assertNotifikasi($this->admin->id, 'undangan_balasan');
        $this->assertSame(1, ActivityLog::where('action', 'INVITE_DECLINE')->count());

        $b = $this->diundang($this->extras('lain_tolak'), $p);
        $this->actingAs($b->extras->user)->post(route('extras.undangan.tolak', $b));
        $this->assertSame('Menolak undangan', $b->fresh()->alasan_tolak);
    }

    public function test_tiga_penolakan_undangan_tidak_membuat_melanggar(): void
    {
        $e = $this->extras();
        foreach ([1, 2, 3, 4] as $i) {
            $a = $this->diundang($e, $this->proyek("P{$i}", [$i]));
            $this->actingAs($e->user)->post(route('extras.undangan.tolak', $a));
        }
        $this->assertSame('aktif', $e->fresh()->status);
    }

    public function test_admin_batalkan_undangan_buka_slot_tanpa_cancellations(): void
    {
        $e = $this->extras();
        $p = $this->proyek('Satu Slot', [10], 1);
        $a = $this->diundang($e, $p);
        $kelasId = $p->classes()->value('id');
        $this->assertSame(0, CastingProjectClass::withTerisi()->find($kelasId)->sisaKuota());

        $this->actingAs($this->admin)->post(route('admin.undangan.batal', $a))->assertSessionHas('status');
        $this->assertSame('dibatalkan', $a->fresh()->status_partisipasi);
        $this->assertSame(0, $a->cancellations()->count());
        $this->assertSame(1, CastingProjectClass::withTerisi()->find($kelasId)->sisaKuota());
        $this->assertCount(1, $this->notifikasi($e->user_id, 'undangan_proyek'));
        $this->assertStringContainsString('dibatalkan', $this->notifikasi($e->user_id, 'undangan_proyek')[0]['pesan']);
        $this->assertSame(1, ActivityLog::where('action', 'INVITE_CANCEL')->count());

        $this->actingAs($this->admin)->post(route('admin.undangan.batal', $a))->assertSessionHas('error');
    }

    public function test_pihak_lain_tidak_boleh_terima_tolak_atau_batalkan(): void
    {
        $e = $this->extras();
        $a = $this->diundang($e, $this->proyek());
        $orangLain = $this->extras('orang_lain')->user;
        $client = $a->castingProject->client;
        $korlap = User::factory()->create(['role' => 'korlap']);

        foreach ([$orangLain, $client, $korlap] as $u) {
            $this->actingAs($u)->post(route('extras.undangan.terima', $a))->assertForbidden();
            $this->actingAs($u)->post(route('extras.undangan.tolak', $a))->assertForbidden();
            $this->actingAs($u)->post(route('admin.undangan.batal', $a))->assertForbidden();
            $this->actingAs($u)->post(route('admin.undangan.store', $e->user), ['casting_project_id' => $a->casting_project_id])->assertForbidden();
            $this->actingAs($u)->get(route('admin.undangan.form', $e->user))->assertForbidden();
        }
        $this->actingAs($e->user)->post(route('admin.undangan.batal', $a))->assertForbidden();
        $this->assertSame('diundang', $a->fresh()->status_partisipasi);
    }

    public function test_client_tidak_melihat_diundang_di_greenlight(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $this->diundang($e, $p);
        $client = $p->client;

        $this->actingAs($client)->get(route('client.reviews.show', $p))->assertOk()
            ->assertViewHas('applications', fn ($c) => $c->isEmpty())->assertDontSee('@budi_ce');
        $this->actingAs($client)->get(route('client.reviews.index'))->assertOk();
        $this->actingAs($client)->get(route('client.extras.profil', $e->user))->assertForbidden();
    }

    public function test_admin_ringkasan_dan_perlu_tindakan_tidak_error_dan_tidak_menghitung_diundang(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $a = $this->diundang($e, $p);

        $this->assertSame([], AdminRingkasan::perluPerProyek([$p->id]));
        AdminRingkasan::untuk();
        AdminRingkasan::tahapan();

        $a->load('extras.user', 'castingProject', 'castingProjectClass', 'contract', 'payment', 'feeNegotiations');
        $langkah = (new \ReflectionMethod(AdminRingkasan::class, 'langkah'))->invoke(null, $a);
        $this->assertSame('Menunggu persetujuan Extras', $langkah['teks']);
        $this->assertFalse($langkah['perlu']);

        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.projects.index'))->assertOk()->assertDontSee('Perlu tindakan (', false);
        $this->actingAs($this->admin)->get(route('admin.projects.show', $p).'?tab=pendaftar')->assertOk()->assertSee('Diundang');
    }

    public function test_lineup_baris_diundang_hanya_batalkan_dan_wa_dan_bulk_mengabaikan(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $a = $this->diundang($e, $p);

        $html = $this->actingAs($this->admin)->get(route('admin.projects.applicants', $p))->assertOk()
            ->assertSee('Diundang')->assertSee('Batalkan undangan')->assertSee('Hubungi via WA')
            ->assertDontSee('Mulai Nego')->assertDontSee('id="detail-'.$a->id.'"', false)
            ->assertDontSee('name="ids[]"', false)
            ->getContent();
        $this->assertStringContainsString('value="diundang"', $html);

        $this->actingAs($this->admin)->post(route('admin.projects.applicants.bulk', $p), ['ids' => [$a->id], 'aksi' => 'grade', 'grade' => 'A'])->assertRedirect();
        $this->actingAs($this->admin)->post(route('admin.projects.applicants.bulk', $p), ['ids' => [$a->id], 'aksi' => 'tolak', 'alasan_tolak' => 'x'])->assertRedirect();
        $this->actingAs($this->admin)->patch(route('admin.applications.grade', $a), ['grade' => 'A']);
        $this->actingAs($this->admin)->patch(route('admin.applications.reject', $a), ['alasan_tolak' => 'x']);
        $a->refresh();
        $this->assertSame('diundang', $a->status_partisipasi);
        $this->assertNull($a->grade);

        $this->actingAs($this->admin)->post(route('admin.negotiations.ajukan', $a), ['nominal' => 100000]);
        $this->actingAs($this->admin)->post(route('admin.negotiations.counter', $a), ['nominal' => 100000])->assertStatus(422);
        $this->assertSame('diundang', $a->fresh()->status_partisipasi);
        $this->assertSame(0, $a->feeNegotiations()->count());
    }

    public function test_pesan_wa_undangan_berisi_proyek_peran_dan_url_tanpa_nominal_atau_client(): void
    {
        $e = $this->extras();
        $p = $this->proyek();
        $this->diundang($e, $p);

        $html = $this->actingAs($this->admin)->get(route('admin.projects.applicants', $p))->getContent();
        preg_match('#https://wa\.me/6281234567800\?text=([^"]+)#', $html, $m);
        $pesan = rawurldecode(html_entity_decode($m[1]));

        $this->assertStringContainsString('saya Rina Admin dari JBTB Casting', $pesan);
        $this->assertStringContainsString('proyek Film CE ('.$p->kode_proyek.') (peran Warga)', $pesan);
        $this->assertStringContainsString(route('extras.projects.show', $p), $pesan);
        $this->assertStringNotContainsString('PH Rahasia', $pesan);
        $this->assertStringNotContainsString('777', $pesan);
        $this->assertStringNotContainsString('Rp', $pesan);
    }

    public function test_extras_lihat_undangan_di_proyek_ditutup_dan_dashboard(): void
    {
        $e = $this->extras();
        $p = $this->proyek('Tertutup', [10], 3, ['status' => 'ditutup']);
        $a = $this->diundang($e, $p);

        $this->actingAs($e->user)->get(route('extras.projects.show', $p))->assertOk()
            ->assertSee('Kamu diundang ke proyek ini')->assertSee(route('extras.undangan.terima', $a), false)->assertSee(route('extras.undangan.tolak', $a), false)
            ->assertDontSee('Daftar ke Proyek Ini')->assertDontSee('sudah tidak menerima pendaftaran');
        $this->actingAs($e->user)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Undangan proyek')->assertSee('Diundang')->assertSee(route('extras.undangan.terima', $a), false);
    }

    public function test_extras_lain_tidak_melihat_banner_undangan(): void
    {
        $a = $this->diundang($this->extras(), $this->proyek());
        $lain = $this->extras('pengunjung');

        $this->actingAs($lain->user)->get(route('extras.projects.show', $a->castingProject))->assertOk()->assertDontSee('Kamu diundang ke proyek ini');
    }

    public function test_tombol_undang_dan_dialog_hanya_untuk_admin(): void
    {
        $e = $this->extras();
        $this->actingAs($this->admin)->get(route('admin.akun.extras'))->assertOk()
            ->assertSee('Undang ke proyek…')->assertSee(route('admin.undangan.form', $e->user), false)->assertSee('id="undang-dialog"', false);
        $this->actingAs($this->admin)->get(route('admin.extras.profil', [$e->user, 'partial' => 1]))->assertSee('Undang ke proyek…');
        $this->actingAs($e->user)->get(route('extras.dashboard'))->assertDontSee('Undang ke proyek')->assertDontSee('undang-dialog');
        $this->actingAs(User::factory()->create(['role' => 'client']))->get(route('client.dashboard'))->assertDontSee('undang-dialog');
    }

    public function test_label_dan_badge_diundang(): void
    {
        $this->assertSame('Diundang', ProjectApplication::LABELS['diundang']);
        $this->assertSame('badge-info', ProjectApplication::BADGES['diundang']);
        foreach (['STATUS_AKTIF', 'STATUS_PASTI', 'STATUS_LOLOS_KE_ATAS', 'STATUS_PROSES'] as $k) {
            $this->assertNotContains('diundang', constant(ProjectApplication::class.'::'.$k));
        }
    }
}
