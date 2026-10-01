<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** BR.2: Kelola Akun ▸ Client untuk Admin, read-only + notif keputusan Client ke Admin PIC. */
class BrAkunClientTest extends TestCase
{
    use RefreshDatabase;

    private function app(CastingProject $p, string $username, string $status, ?string $keputusan = null, ?string $grade = null, int $menitLalu = 0): ProjectApplication
    {
        $ex = ExtrasProfile::factory()->for(User::factory()->state(['role' => 'extras', 'username' => $username]))->create();
        $a = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $ex->id, 'status_partisipasi' => $status]);
        if ($keputusan) {
            $r = ClientReview::create(['project_application_id' => $a->id, 'client_id' => $p->client_id, 'keputusan' => $keputusan, 'grade_client' => $grade]);
            $r->forceFill(['created_at' => now()->subMinutes($menitLalu)])->save();
        }

        return $a;
    }

    private function data(): array
    {
        $client = User::factory()->create(['role' => 'client', 'name' => 'Client Andini', 'nama_perusahaan' => 'PT Andini Film']);
        User::factory()->create(['role' => 'client', 'name' => 'Client Budi', 'nama_perusahaan' => 'Budi Pictures']);
        $p = CastingProject::factory()->create(['client_id' => $client->id, 'nama_produksi' => 'Iklan Minuman']);
        $this->app($p, 'dimas_rk', 'lolos', 'approve', 'A', 5);
        $this->app($p, 'sari_tolak', 'ditolak', 'reject', null, 10);
        $this->app($p, 'rina_tunggu', 'diajukan_ke_client');
        $this->app($p, 'admin_tolak', 'ditolak');
        $this->app($p, 'baru_daftar', 'diajukan');

        return [$client, $p];
    }

    public function test_sidebar_submenu_client_dan_halaman_read_only(): void
    {
        [, $p] = $this->data();
        $admin = User::factory()->create(['role' => 'admin']);

        $res = $this->actingAs($admin)->get(route('admin.akun.client'))->assertOk();
        $res->assertSeeInOrder(['Kelola Akun', 'Extras', 'Client', 'Riwayat Kerja'])
            ->assertSee('Client Andini')->assertSee('PT Andini Film')->assertSee('1 proyek')->assertSee('terakhir: Iklan Minuman')
            ->assertSee($p->kode_proyek)
            ->assertSeeInOrder(['3 diajukan', '1 lock', '1 ditolak'])
            ->assertSee('@dimas_rk')->assertSee('Lock · Grade A')->assertSee('@rina_tunggu')->assertSee('Menunggu keputusan')->assertSee('@sari_tolak')
            ->assertDontSee('@admin_tolak')->assertDontSee('@baru_daftar')
            ->assertSee('data-profil-modal', false)
            ->assertDontSee('Keputusan Client terbaru')->assertDontSee('data-feed-keputusan', false)->assertDontSee('akc-kanan', false);

        // read-only: isi halaman (dalam <main>) tanpa form POST, tanpa aksi edit/nonaktif/reset
        $html = $res->getContent();
        $isi = substr($html, strpos($html, 'Riwayat Client:'), strpos($html, '</main>') - strpos($html, 'Riwayat Client:'));
        $this->assertStringNotContainsString('method="POST"', $isi);
        $this->assertStringNotContainsString('<button', $isi);
        $this->assertStringNotContainsString(route('admin.users.toggle-status', $p->client_id), $html);
        $this->assertStringNotContainsString('reset-password', $html);
    }

    public function test_search_live_dan_tanpa_n_plus_1(): void
    {
        $this->data();
        foreach (range(1, 5) as $i) {
            $c = User::factory()->create(['role' => 'client']);
            $pp = CastingProject::factory()->create(['client_id' => $c->id]);
            $this->app($pp, "ex_{$i}", 'lolos', 'approve', 'B');
        }
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.akun.client', ['q' => 'budi']))
            ->assertOk()->assertSee('Client Budi')->assertViewHas('clients', fn ($c) => $c->pluck('name')->all() === ['Client Budi'])
            ->assertSee('data-live', false);

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.akun.client'))->assertOk();
        $n = count(DB::getQueryLog());
        DB::flushQueryLog();
        foreach (range(6, 15) as $i) {
            $c = User::factory()->create(['role' => 'client']);
            $this->app(CastingProject::factory()->create(['client_id' => $c->id]), "ex_{$i}", 'ditolak', 'reject');
        }
        DB::flushQueryLog();
        $this->actingAs($admin)->get(route('admin.akun.client'))->assertOk();
        $this->assertSame($n, count(DB::getQueryLog()));
    }

    public function test_dashboard_tanpa_feed_dan_endpoint_dihapus(): void
    {
        $this->assertFalse(Route::has('admin.akun.client.keputusan'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.dashboard'))->assertOk()->assertDontSee('Keputusan Client terbaru');
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->get(route('super-admin.dashboard'))->assertOk()->assertDontSee('Keputusan Client terbaru');
    }

    public function test_anchor_proyek_membuka_accordion_client_dan_proyek(): void
    {
        [$client, $p] = $this->data();
        $admin = User::factory()->create(['role' => 'admin']);

        $html = $this->actingAs($admin)->get(route('admin.akun.client', ['q' => $client->name, 'proyek' => $p->id]))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('#id="client-'.$client->id.'"\s+open#', $html);
        $this->assertMatchesRegularExpression('#id="proyek-'.$p->id.'"\s+open#', $html);
        $this->assertDoesNotMatchRegularExpression('#id="proyek-'.$p->id.'"\s+open#', $this->actingAs($admin)->get(route('admin.akun.client'))->getContent());
    }

    private function putuskan(string $keputusan, int $jumlah = 1): array
    {
        $client = User::factory()->create(['role' => 'client', 'name' => 'Client Andini']);
        $p = CastingProject::factory()->create(['client_id' => $client->id, 'nama_produksi' => 'Iklan Minuman']);
        $apps = collect(range(1, $jumlah))->map(fn ($i) => $this->app($p, "kandidat_{$i}", 'diajukan_ke_client'));
        $this->actingAs($client)->post(route('client.reviews.review'), [
            'application_ids' => $apps->pluck('id')->all(), 'keputusan' => $keputusan, 'grade_client' => $keputusan === 'approve' ? 'A' : null,
        ])->assertRedirect();

        return [$p->admin->notifications()->where('data->jenis', 'keputusan_client')->get(), $p];
    }

    public function test_client_lock_admin_pic_dapat_notif(): void
    {
        [$notif, $p] = $this->putuskan('approve');
        $this->assertCount(1, $notif);
        $d = $notif->first()->data;
        $this->assertSame('Client Lock Kandidat', $d['judul']);
        $this->assertSame("Client Andini me-lock @kandidat_1 di proyek {$p->namaKode()} (Grade A).", $d['pesan']);
        $this->assertSame('/admin/akun/client?q=Client%20Andini&proyek='.$p->id.'#proyek-'.$p->id, $d['url']);
        $this->assertSame($p->admin->notifications()->count(), $p->admin->notifications()->get()->unique(fn ($n) => $n->data['pesan'])->count());
        $this->assertSame(0, User::where('role', 'admin')->whereKeyNot($p->admin_id)->withCount('notifications')->get()->sum('notifications_count'));
    }

    public function test_client_tolak_admin_pic_dapat_notif(): void
    {
        [$notif, $p] = $this->putuskan('reject');
        $this->assertCount(1, $notif);
        $this->assertSame('Client Menolak Kandidat', $notif->first()->data['judul']);
        $this->assertSame("Client Andini menolak @kandidat_1 di proyek {$p->namaKode()}.", $notif->first()->data['pesan']);
        $this->assertSame(1, $p->admin->notifications()->count());
    }

    public function test_bulk_satu_notif_ringkas_per_proyek(): void
    {
        [$notif, $p] = $this->putuskan('reject', 7);
        $this->assertCount(1, $notif);
        $this->assertSame("Client Andini menolak @kandidat_1, @kandidat_2, @kandidat_3, @kandidat_4, @kandidat_5 +2 lainnya di proyek {$p->namaKode()}.", $notif->first()->data['pesan']);
    }

    public static function bukanAdminProvider(): array
    {
        return [['korlap'], ['client'], ['extras']];
    }

    #[DataProvider('bukanAdminProvider')]
    public function test_role_lain_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user)->get(route('admin.akun.client'))->assertForbidden();
    }
}
