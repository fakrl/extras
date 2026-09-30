<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BkJadwalBentrokTest extends TestCase
{
    use RefreshDatabase;

    private User $extras;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $this->extras->id, 'foto_profil_path' => 'x.jpg', 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);
    }

    private function proyek(string $nama, array $hari): CastingProject
    {
        $p = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => $nama, 'deadline' => now()->addDays(3), 'kuota' => 10, 'status' => 'dibuka',
        ]);
        $p->classes()->create(['nama_kelas' => 'Warga', 'budget_client' => 1, 'kuota_kelas' => 5]);
        foreach ($hari as $h) {
            $p->shootingDates()->create(['tanggal' => now()->addDays($h)->toDateString()]);
        }

        return $p;
    }

    private function daftar(CastingProject $p, string $status): ProjectApplication
    {
        return $p->applications()->create(['extras_id' => $this->extras->extrasProfile->id, 'casting_project_class_id' => $p->classes->first()->id, 'status_partisipasi' => $status]);
    }

    private function apply(CastingProject $p, array $extra = [])
    {
        return $this->actingAs($this->extras)->from(route('extras.projects.show', $p))
            ->post(route('extras.projects.apply', $p), ['casting_project_class_id' => $p->classes->first()->id] + $extra);
    }

    public function test_a_daftar_b_saat_a_sudah_kontrak_di_tanggal_sama_ditolak(): void
    {
        $this->daftar($this->proyek('Film A', [5]), 'kontrak_ditandatangani');
        $b = $this->proyek('Iklan B', [5, 6]);

        $this->actingAs($this->extras)->get(route('extras.projects.show', $b))
            ->assertSee('Bentrok dengan jadwalmu')->assertSee('Film A (sudah pasti)');

        $this->apply($b, ['konfirmasi_bentrok' => 1])
            ->assertRedirect(route('extras.projects.show', $b))
            ->assertSessionHas('error', 'Kamu sudah terjadwal syuting Film A tanggal '.now()->addDays(5)->translatedFormat('d M Y').'. Batalkan dulu yang itu kalau mau ikut proyek ini.');
        $this->assertSame(0, $b->applications()->count());
    }

    public function test_b_daftar_saat_a_masih_nego_boleh_setelah_konfirmasi_dan_flag_dua_duanya(): void
    {
        $a = $this->daftar($this->proyek('Film A', [5]), 'nego_fee');
        $b = $this->proyek('Iklan B', [5]);
        $pesan = 'Tanggal ini bentrok dengan Film A yang masih diproses. Kalau dua-duanya lolos, kamu wajib pilih salah satu.';

        $this->apply($b)->assertRedirect(route('extras.projects.show', $b))->assertSessionHas('konfirmasi_bentrok', $pesan);
        $this->assertSame(0, $b->applications()->count());

        $this->actingAs($this->extras)->withSession(['konfirmasi_bentrok' => $pesan, '_old_input' => ['casting_project_class_id' => $b->classes->first()->id]])
            ->get(route('extras.projects.show', $b))
            ->assertSee('id="dialog-bentrok"', false)->assertSee($pesan)->assertSee('Tetap daftar')
            ->assertSee('name="konfirmasi_bentrok" value="1"', false)
            ->assertSee('name="casting_project_class_id" value="'.$b->classes->first()->id.'"', false);

        $this->apply($b, ['konfirmasi_bentrok' => 1])->assertRedirect(route('extras.dashboard'));
        $this->assertTrue($b->applications()->sole()->bentrok_jadwal_flag);
        $this->assertTrue($a->fresh()->bentrok_jadwal_flag);
    }

    public function test_c_a_jadi_lolos_b_masuk_perlu_tindakan_dan_admin_b_dikabari(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $pa = $this->proyek('Film A', [5]);
        $pa->update(['client_id' => $client->id]);
        $a = $this->daftar($pa, 'diajukan_ke_cd');
        $b = $this->daftar($pb = $this->proyek('Iklan B', [5]), 'nego_fee');

        $this->actingAs($client)->post(route('cd.reviews.review'), ['application_ids' => [$a->id], 'keputusan' => 'approve', 'grade_cd' => 'A'])->assertRedirect();

        $this->assertSame('lolos', $a->fresh()->status_partisipasi);
        $this->assertTrue($a->fresh()->bentrok_jadwal_flag);
        $this->assertTrue($b->fresh()->bentrok_jadwal_flag);
        $this->assertSame(1, $pb->admin->notifications()->count());
        $this->assertSame(0, $pa->admin->notifications()->count());
        $this->assertTrue($this->extras->notifications()->get()->contains(fn ($n) => $n->data['judul'] === 'Jadwal Bentrok'));

        $html = $this->actingAs($this->extras)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Jadwal bentrok: <em>Film A</em> sudah pasti, <em>Iklan B</em> tanggal sama. Batalkan <em>Iklan B</em>?', false)
            ->assertSee('href="#pendaftaran-'.$b->id.'"', false)
            ->assertSee('Bentrok jadwal dengan Film A')->assertSee('Bentrok jadwal dengan Iklan B')
            ->assertSee('id="dialog-batal-bentrok-'.$b->id.'"', false)
            ->assertSee('Pembatalan ini dihitung sebagai batal mendadak.')
            ->getContent();
        $this->assertStringNotContainsString('id="dialog-batal-bentrok-'.$a->id.'"', $html);
    }

    public function test_d23_batal_karena_bentrok_tetap_dihitung_batal_mendadak(): void
    {
        $this->daftar($this->proyek('Film A', [1]), 'lolos');
        $b = $this->daftar($this->proyek('Iklan B', [1]), 'diajukan');

        $this->actingAs($this->extras)->post(route('extras.negotiations.batalkan', $b), ['alasan' => 'Bentrok jadwal'])
            ->assertSessionHas('status', 'Pendaftaran dibatalkan.');

        $this->assertSame('dibatalkan', $b->fresh()->status_partisipasi);
        $this->assertTrue($b->cancellations()->sole()->is_mendadak);
        $this->assertSame('Bentrok jadwal', $b->cancellations()->sole()->alasan);
        $this->assertSame(1, $this->extras->extrasProfile->fresh()->cancel_count);
    }

    public function test_pendaftaran_proses_tanpa_bentrok_tetap_tidak_bisa_dibatalkan(): void
    {
        $b = $this->daftar($this->proyek('Iklan B', [1]), 'diajukan');

        $this->actingAs($this->extras)->post(route('extras.negotiations.batalkan', $b), ['alasan' => 'x'])
            ->assertSessionHas('status', 'Hanya aplikasi berstatus Deal, Lolos, atau Kontrak Ditandatangani yang bisa dibatalkan.');
        $this->assertSame('diajukan', $b->fresh()->status_partisipasi);
    }

    public function test_d_ttd_kontrak_b_saat_a_sudah_ttd_tanggal_sama_ditolak_admin_tetap_boleh(): void
    {
        Storage::fake('local');
        $a = $this->daftar($this->proyek('Film A', [5]), 'kontrak_ditandatangani');
        $b = $this->daftar($pb = $this->proyek('Iklan B', [5]), 'lolos');
        $b->contract()->create([]);
        $ttd = ['signature' => 'data:image/png;base64,'.base64_encode('x')];

        $this->actingAs($this->extras)->post(route('contracts.sign', $b), $ttd)
            ->assertSessionHas('error', 'Kamu sudah tanda tangan kontrak Film A di tanggal yang sama ('.now()->addDays(5)->translatedFormat('d M Y').'). Batalkan dulu yang itu kalau mau ikut proyek ini.')
            ->assertSessionHas('bentrok_link', route('extras.dashboard').'#pendaftaran-'.$a->id);
        $this->assertNull($b->contract->fresh()->ttd_extras_signature_path);

        $this->actingAs($pb->admin)->post(route('contracts.sign', $b), $ttd)->assertSessionHas('status');
        $this->assertNotNull($b->contract->fresh()->ttd_admin_signature_path);
    }

    public function test_e_admin_tambah_tanggal_di_a_bentrok_dengan_kontrak_b_flag_dan_notif(): void
    {
        $pa = $this->proyek('Film A', [3]);
        $client = User::factory()->create(['role' => 'client']);
        $pa->update(['client_id' => $client->id]);
        $a = $this->daftar($pa, 'deal');
        $b = $this->daftar($pb = $this->proyek('Iklan B', [7]), 'kontrak_ditandatangani');
        $payload = [
            'nama_produksi' => 'Film A', 'client_id' => $client->id, 'deadline' => now()->addDays(3)->toDateString(), 'kuota' => 10,
            'kelas' => [['id' => $pa->classes->first()->id, 'nama_kelas' => 'Warga', 'budget_client' => 1, 'kuota_kelas' => 5]],
        ];

        $this->actingAs($pa->admin)->patch(route('admin.projects.update', $pa), $payload + ['tanggal_shooting' => [now()->addDays(3)->toDateString()]])->assertRedirect();
        $this->assertFalse($a->fresh()->bentrok_jadwal_flag);
        $this->assertSame(0, $this->extras->notifications()->count());

        $this->actingAs($pa->admin)->patch(route('admin.projects.update', $pa), $payload + ['tanggal_shooting' => [now()->addDays(3)->toDateString(), now()->addDays(7)->toDateString()]])->assertRedirect();

        $this->assertTrue($a->fresh()->bentrok_jadwal_flag);
        $this->assertTrue($b->fresh()->bentrok_jadwal_flag);
        $this->assertSame(1, $this->extras->notifications()->count());
        $this->assertSame(1, $pa->admin->notifications()->count());
        $this->assertSame(1, $pb->admin->notifications()->count());

        $this->actingAs($this->extras)->get(route('extras.dashboard'))
            ->assertSee('Jadwal bentrok: <em>Iklan B</em> sudah pasti, <em>Film A</em> tanggal sama. Batalkan <em>Film A</em>?', false);
    }

    public function test_client_tambah_tanggal_baru_bentrok_dikabari_edit_tanggal_lama_tidak(): void
    {
        $client = User::factory()->create(['role' => 'client']);
        $pa = $this->proyek('Film A', [3]);
        $pa->update(['client_id' => $client->id]);
        $this->daftar($pa, 'lolos');
        $b = $this->daftar($this->proyek('Iklan B', [3, 7]), 'nego_fee');

        $this->actingAs($client)->post(route('cd.jadwal.store', $pa), ['tanggal' => now()->addDays(3)->toDateString(), 'lokasi' => 'Studio'])->assertRedirect();
        $this->assertSame(0, $this->extras->notifications()->count());

        $this->actingAs($client)->post(route('cd.jadwal.store', $pa), ['tanggal' => now()->addDays(7)->toDateString()])->assertRedirect();
        $this->assertTrue($b->fresh()->bentrok_jadwal_flag);
        $this->assertSame(1, $this->extras->notifications()->count());
    }
}
