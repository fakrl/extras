<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CaSelesaikanSengketaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $extrasUser;

    private ProjectApplication $aplikasi;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $this->extrasUser->id]);
        $project = CastingProject::create(['admin_id' => $this->admin->id, 'nama_produksi' => 'Proyek CA', 'deadline' => now()->addDays(7), 'kuota' => 5]);
        $this->aplikasi = ProjectApplication::create(['casting_project_id' => $project->id, 'extras_id' => $extras->id, 'status_partisipasi' => 'kontrak_ditandatangani', 'fee_final' => 200000]);
        Storage::disk('local')->put('payments/bukti-transfer/lama.png', 'x');
        $this->aplikasi->payment()->create(['status' => 'ditransfer', 'bukti_transfer_path' => 'payments/bukti-transfer/lama.png', 'ditransfer_at' => now()->subDay()]);
    }

    private function lapor(): void
    {
        $this->actingAs($this->extrasUser)->post(route('payments.sengketa', $this->aplikasi), ['alasan' => 'Kurang 1 hari'])->assertSessionHasNoErrors();
    }

    private function selesaikan(User $u, array $data = ['catatan' => 'Sudah dikoreksi'])
    {
        return $this->actingAs($u)->post(route('payments.selesaikan-sengketa', $this->aplikasi), $data);
    }

    public function test_lapor_mengabari_admin_pic(): void
    {
        $this->lapor();
        $n = $this->admin->notifications()->where('data->jenis', 'sengketa_pembayaran')->first();
        $this->assertNotNull($n);
        $this->assertStringContainsString('Kurang 1 hari', $n->data['message'] ?? json_encode($n->data));
    }

    public function test_selesai_tanpa_bukti_baru(): void
    {
        $this->lapor();
        $lama = $this->aplikasi->payment->fresh()->ditransfer_at;
        $this->selesaikan($this->admin)->assertSessionHas('status');
        $p = $this->aplikasi->payment->fresh();
        $this->assertSame('ditransfer', $p->status);
        $this->assertSame('Sudah dikoreksi', $p->catatan_penyelesaian);
        $this->assertSame('Kurang 1 hari', $p->alasan_sengketa);
        $this->assertSame('payments/bukti-transfer/lama.png', $p->bukti_transfer_path);
        $this->assertTrue($p->ditransfer_at->equalTo($lama));
        Storage::disk('local')->assertExists('payments/bukti-transfer/lama.png');
        $this->assertTrue(ActivityLog::where('action', 'RESOLVE_DISPUTE')->exists());
        $this->assertNotNull($this->extrasUser->notifications()->where('data->jenis', 'sengketa_ditanggapi')->first());
    }

    public function test_selesai_dengan_bukti_baru_menghapus_file_lama(): void
    {
        $this->lapor();
        $lama = $this->aplikasi->payment->fresh()->ditransfer_at;
        $this->travel(2)->hours();
        $this->selesaikan($this->admin, ['catatan' => 'Transfer ulang', 'bukti_transfer' => UploadedFile::fake()->image('baru.png')]);
        $p = $this->aplikasi->payment->fresh();
        $this->assertNotSame('payments/bukti-transfer/lama.png', $p->bukti_transfer_path);
        Storage::disk('local')->assertMissing('payments/bukti-transfer/lama.png');
        Storage::disk('local')->assertExists($p->bukti_transfer_path);
        $this->assertTrue($p->ditransfer_at->gt($lama));
        $this->assertSame('Kurang 1 hari', $p->alasan_sengketa);
    }

    public function test_catatan_kosong_ditolak(): void
    {
        $this->lapor();
        $this->selesaikan($this->admin, ['catatan' => ''])->assertSessionHasErrors('catatan');
        $this->assertSame('disengketakan', $this->aplikasi->payment->fresh()->status);
    }

    public function test_korlap_dan_extras_403(): void
    {
        $this->lapor();
        $this->selesaikan(User::factory()->create(['role' => 'korlap']))->assertForbidden();
        $this->selesaikan($this->extrasUser)->assertForbidden();
        $this->assertSame('disengketakan', $this->aplikasi->payment->fresh()->status);
    }

    public function test_status_bukan_disengketakan_ditolak(): void
    {
        $this->selesaikan($this->admin)->assertSessionHas('error');
        $p = $this->aplikasi->payment->fresh();
        $this->assertSame('ditransfer', $p->status);
        $this->assertNull($p->catatan_penyelesaian);
    }

    public function test_extras_konfirmasi_atau_lapor_lagi_setelah_ditanggapi(): void
    {
        $this->lapor();
        $this->selesaikan($this->admin);
        $this->actingAs($this->extrasUser)->get(route('payments.show', $this->aplikasi))->assertSee('Tanggapan Admin')->assertSee('Sudah dikoreksi');
        $this->actingAs($this->extrasUser)->post(route('payments.sengketa', $this->aplikasi), ['alasan' => 'Masih kurang']);
        $this->assertSame('disengketakan', $this->aplikasi->payment->fresh()->status);
        $this->selesaikan($this->admin, ['catatan' => 'Ok']);
        $this->actingAs($this->extrasUser)->post(route('payments.confirm', $this->aplikasi));
        $this->assertSame('dikonfirmasi_diterima', $this->aplikasi->payment->fresh()->status);
    }

    public function test_super_admin_godmode_bisa_menyelesaikan_dan_form_tampil(): void
    {
        $this->lapor();
        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($sa)->get(route('payments.show', $this->aplikasi))->assertSee('Selesaikan dan kembalikan ke Extras');
        $this->actingAs($this->extrasUser)->get(route('payments.show', $this->aplikasi))->assertDontSee('Selesaikan dan kembalikan ke Extras');
        $this->selesaikan($sa)->assertSessionHas('status');
        $this->assertSame('ditransfer', $this->aplikasi->payment->fresh()->status);
    }
}
