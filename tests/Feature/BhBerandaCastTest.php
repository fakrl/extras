<?php

namespace Tests\Feature;

use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Database\Seeders\ExtrasCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BhBerandaCastTest extends TestCase
{
    use RefreshDatabase;

    private function extras(string $username, bool $izin = true, bool $tampil = true): ExtrasProfile
    {
        $user = User::factory()->create(['role' => 'extras', 'username' => $username, 'name' => 'Nama Asli '.$username, 'nomor_wa' => '0812999'.strlen($username)]);
        $profile = ExtrasProfile::create(['user_id' => $user->id, 'nama_asli' => 'Nama Asli '.$username, 'usia' => 37, 'foto_profil_path' => 'x/'.$username.'.jpg']);
        $profile->forceFill(['izin_tampil_publik' => $izin, 'tampil_di_beranda' => $tampil, 'tampil_di_beranda_at' => now()])->save();
        $profile->generateShareToken();

        return $profile;
    }

    public function test_toggle_ditolak_tanpa_izin_dan_jalan_dengan_izin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tanpaIzin = $this->extras('tanpa_izin', false, false);
        $this->actingAs($admin)->from('/x')->patch(route('admin.extras.beranda', $tanpaIzin->user_id))
            ->assertRedirect('/x')->assertSessionHas('error');
        $this->assertFalse($tanpaIzin->fresh()->tampil_di_beranda);
        $this->actingAs(User::factory()->create(['role' => 'super_admin']))->get(route('super-admin.admins.show', $tanpaIzin->user_id))
            ->assertOk()->assertSee('Belum ada izin dari Extras')->assertSee('disabled', false);

        $izin = $this->extras('izin_ok', true, false);
        $izin->forceFill(['share_token' => null])->save();
        $this->actingAs($admin)->patch(route('admin.extras.beranda', $izin->user_id))->assertSessionHas('status');
        $izin->refresh();
        $this->assertTrue($izin->tampil_di_beranda);
        $this->assertNotNull($izin->share_token);
        $this->assertDatabaseHas('activity_logs', ['action' => 'TOGGLE_EXTRAS_BERANDA', 'user_id' => $admin->id]);

        $this->actingAs(User::factory()->create(['role' => 'korlap']))->patch(route('admin.extras.beranda', $izin->user_id))->assertForbidden();
    }

    public function test_beranda_cuma_yang_izin_dan_disetujui_tanpa_data_pribadi(): void
    {
        $this->seed(ExtrasCategorySeeder::class);
        foreach (['cast_a', 'cast_b', 'cast_c', 'cast_d'] as $u) {
            $this->extras($u)->categories()->sync(ExtrasCategory::whereIn('nama', ['Dewasa', 'Berhijab'])->pluck('id'));
        }
        $this->extras('belum_izin', false, true);
        $this->extras('belum_acc', true, false);
        $nonaktif = $this->extras('akun_mati');
        $nonaktif->user->update(['status' => 'nonaktif']);

        $html = $this->get('/')->assertOk()->assertSee('@cast_a')->assertSee('Dewasa')->assertDontSee('Berhijab')
            ->assertDontSee('@belum_izin')->assertDontSee('@belum_acc')->assertDontSee('@akun_mati')->getContent();
        $this->assertStringNotContainsString('Nama Asli', $html);
        $this->assertStringNotContainsString('37 th', $html);
        $this->assertStringNotContainsString('0812999', $html);
    }

    public function test_extras_matikan_izin_langsung_hilang_dan_kurang_dari_4_disembunyikan(): void
    {
        $profiles = collect(['cast_a', 'cast_b', 'cast_c', 'cast_d'])->map(fn ($u) => $this->extras($u));
        $this->get('/')->assertSee('id="cast"', false);

        $user = $profiles[0]->user;
        $this->actingAs($user)->put('/extras/profil', ['nama_asli' => 'X', 'username' => 'cast_a', 'izin_present' => 1])->assertRedirect();
        $this->assertFalse($profiles[0]->fresh()->izin_tampil_publik);
        $this->assertFalse($profiles[0]->fresh()->tampil_di_beranda);

        auth()->logout();
        $this->get('/')->assertOk()->assertDontSee('id="cast"', false)->assertDontSee('@cast_b');
    }

    public function test_form_tanpa_marker_tidak_mereset_izin(): void
    {
        $p = $this->extras('cast_a');
        $this->actingAs($p->user)->put('/extras/profil', ['nama_asli' => 'X', 'username' => 'cast_a'])->assertRedirect();
        $this->assertTrue($p->fresh()->izin_tampil_publik);
        $this->assertTrue($p->fresh()->tampil_di_beranda);

        $this->actingAs($p->user)->get(route('extras.profile.edit'))->assertOk()->assertSee('Izinkan foto &amp; profil saya ditampilkan di website JBTB', false);
    }
}
