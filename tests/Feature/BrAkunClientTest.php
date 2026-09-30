<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ClientReview;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\KeputusanClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** BR.2: Kelola Akun ▸ Client untuk Admin, read-only + feed keputusan Client. */
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
            ->assertSee('Keputusan Client terbaru');

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

    public function test_feed_10_terbaru_dan_endpoint_partial(): void
    {
        [, $p] = $this->data();
        foreach (range(1, 12) as $i) {
            $this->app($p, "lama_{$i}", 'lolos', 'approve', null, 60 + $i);
        }

        $feed = KeputusanClient::terbaru(10);
        $this->assertCount(10, $feed);
        $this->assertSame('dimas_rk', $feed->first()->projectApplication->extras->user->username);
        $this->assertSame('sari_tolak', $feed[1]->projectApplication->extras->user->username);

        $admin = User::factory()->create(['role' => 'admin']);
        $res = $this->actingAs($admin)->get(route('admin.akun.client.keputusan'))->assertOk()
            ->assertSeeInOrder(['Client Andini', 'lock', '@dimas_rk', 'Iklan Minuman', '5 menit yang lalu'])
            ->assertSee('tolak')
            ->assertDontSee('<html', false)
            ->assertDontSee('lama_12');
        $this->assertSame(10, substr_count($res->getContent(), '<li>'));

        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($sa)->get(route('admin.akun.client.keputusan'))->assertOk()->assertSee('@dimas_rk');
        $this->actingAs($sa)->get(route('super-admin.dashboard'))->assertOk()->assertSee('Keputusan Client terbaru')->assertSee('data-feed-keputusan', false);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Keputusan Client terbaru')->assertSee(route('admin.akun.client.keputusan', ['kecil' => 1]), false);
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
        $this->actingAs($user)->get(route('admin.akun.client.keputusan'))->assertForbidden();
    }
}
