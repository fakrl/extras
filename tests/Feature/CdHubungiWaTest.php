<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CD: tombol "Hubungi via WA" khusus Admin/SA, tautan wa.me via User::tautanWa(). */
class CdHubungiWaTest extends TestCase
{
    use RefreshDatabase;

    private const NOMOR = '6281234567899';

    private function extras(?string $wa = '081234567899', string $username = 'budi_cd'): ExtrasProfile
    {
        $user = User::factory()->create(['role' => 'extras', 'username' => $username, 'name' => 'Budi Santoso', 'nomor_wa' => $wa]);

        return ExtrasProfile::factory()->create(['user_id' => $user->id, 'share_token' => 'token'.$username]);
    }

    private function lamar(ExtrasProfile $e, array $proyek = [], string $status = 'diajukan'): ProjectApplication
    {
        $p = CastingProject::factory()->create($proyek + ['client_id' => User::factory()->create(['role' => 'client'])->id]);

        return ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $e->id, 'status_partisipasi' => $status]);
    }

    public function test_helper_tautan_wa_format_dan_encode(): void
    {
        $u = $this->extras()->user;
        $url = $u->tautanWa('Halo, apa kabar? & siap #1');

        $this->assertStringStartsWith('https://wa.me/'.self::NOMOR.'?text=', $url);
        $this->assertSame('Halo, apa kabar? & siap #1', rawurldecode(explode('?text=', $url)[1]));
        $this->assertStringNotContainsString(' ', $url);
        $this->assertNull($this->extras(null, 'tanpa_wa')->user->tautanWa('x'));
    }

    public function test_tombol_tampil_untuk_admin_dan_super_admin_di_kelola_akun_popup_dan_lineup(): void
    {
        $e = $this->extras();
        $app = $this->lamar($e);

        foreach (['admin', 'super_admin'] as $role) {
            $a = User::factory()->create(['role' => $role, 'name' => 'Rina Admin']);
            $pesan = rawurlencode('Halo Budi Santoso, saya Rina Admin dari JBTB Casting. Ada kabar soal casting, apakah kamu sedang available?');

            foreach ([
                route('admin.akun.extras'),
                route('admin.akun.extras', ['tampil' => 'daftar']),
                route('admin.extras.profil', [$e->user, 'partial' => 1]),
                route('admin.projects.applicants', $app->castingProject),
            ] as $url) {
                $this->actingAs($a)->get($url)->assertOk()
                    ->assertSee('https://wa.me/'.self::NOMOR.'?text='.$pesan, false)
                    ->assertSee('Hubungi via WA')->assertSee('ti-brand-whatsapp', false)
                    ->assertSee('target="_blank" rel="noopener"', false);
            }
        }
    }

    public function test_pesan_tidak_memuat_client_atau_nominal(): void
    {
        $e = $this->extras();
        $app = $this->lamar($e);
        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.akun.extras'))->getContent();
        $pesan = rawurldecode(explode('?text=', explode('"', explode('https://wa.me/', $html)[1])[0])[1]);

        $this->assertStringNotContainsString($app->castingProject->namaClient(), $pesan);
        $this->assertDoesNotMatchRegularExpression('/Rp|\d{4,}/', $pesan);
    }

    public function test_tidak_ada_tombol_atau_nomor_untuk_client_korlap_extras_dan_publik(): void
    {
        $e = $this->extras();
        $app = $this->lamar($e, [], 'diajukan_ke_client');
        $client = $app->castingProject->client;

        $cek = function ($res) {
            $res->assertDontSee('Hubungi via WA')->assertDontSee('wa.me/'.self::NOMOR, false)->assertDontSee(self::NOMOR)->assertDontSee('081234567899');
        };

        $cek($this->actingAs($client)->get(route('client.extras.profil', [$e->user, 'partial' => 1]))->assertOk());
        $cek($this->actingAs($client)->get(route('client.reviews.show', $app->castingProject))->assertOk());
        $cek($this->actingAs($e->user)->get(route('extras.profile.show'))->assertOk());
        $cek($this->actingAs($e->user)->get(route('extras.dashboard'))->assertOk());
        $this->actingAs(User::factory()->create(['role' => 'korlap']))->get(route('admin.akun.extras'))->assertForbidden();
        $cek($this->actingAs(User::factory()->create(['role' => 'korlap']))->get(route('admin.attendance.index'))->assertOk());

        auth()->logout();
        $cek($this->get(route('public.extras.profile', 'tokenbudi_cd'))->assertOk());
        $cek($this->get(route('home'))->assertOk());
    }

    public function test_extras_tanpa_nomor_wa_tidak_punya_tombol(): void
    {
        $e = $this->extras(null);
        $app = $this->lamar($e);
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([route('admin.akun.extras'), route('admin.akun.extras', ['tampil' => 'daftar']), route('admin.extras.profil', [$e->user, 'partial' => 1]), route('admin.projects.applicants', $app->castingProject)] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()->assertDontSee('Hubungi via WA')->assertDontSee('wa.me', false);
        }
    }
}
