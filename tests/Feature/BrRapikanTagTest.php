<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** BR.5: halaman Kelola Tag diganti dialog "Rapikan tag" di panel filter Kelola Akun (Admin/SA). */
class BrRapikanTagTest extends TestCase
{
    use RefreshDatabase;

    private function profil(): ExtrasProfile
    {
        return ExtrasProfile::factory()->create();
    }

    private function data(): array
    {
        [$a, $b] = [$this->profil(), $this->profil()];
        $lainnya = ExtrasCategory::create(['nama' => 'hijab']);
        $typo = ExtrasCategory::create(['nama' => 'Nyetir motr', 'grup' => 'Kemampuan']);
        $rapi = ExtrasCategory::create(['nama' => 'Silat', 'grup' => 'Kemampuan']);
        $bawaan = ExtrasCategory::create(['nama' => 'Berhijab', 'grup' => 'Tipe']);
        $a->categories()->attach([$lainnya->id, $typo->id, $rapi->id]);
        $b->categories()->attach([$lainnya->id, $rapi->id]);

        return compact('lainnya', 'typo', 'rapi', 'bawaan');
    }

    public function test_link_rapikan_n_dan_dialog_cuma_tag_perlu_perhatian(): void
    {
        $t = $this->data();
        $this->assertEqualsCanonicalizing([$t['lainnya']->id, $t['typo']->id], ExtrasCategory::perluDirapikan()->pluck('id')->all());

        // CM.3.3: halaman /super-admin/akun murni tugas SA, pakai route super-admin.tags.* sendiri
        // (bukan admin.tags.*) supaya tidak ikut digerbang CheckRole sebagai "aksi admin".
        foreach ([['admin', 'admin.akun.extras', [], 'admin.tags'], ['super_admin', 'super-admin.akun.index', ['role' => 'extras'], 'super-admin.tags']] as [$role, $route, $q, $tagRoute]) {
            $res = $this->actingAs(User::factory()->create(['role' => $role]))->get(route($route, $q))->assertOk()
                ->assertSee('Kelola tag (2 perlu dirapikan)')
                ->assertSee('<dialog id="rapikan-tag"', false)
                ->assertDontSee('Kelola Tag')
                ->assertDontSee(route("{$tagRoute}.update", $t['rapi']), false)
                ->assertDontSee(route("{$tagRoute}.update", $t['bawaan']), false);
            foreach (['lainnya', 'typo'] as $k) {
                $res->assertSee(route("{$tagRoute}.update", $t[$k]), false)
                    ->assertSee(route("{$tagRoute}.gabung", $t[$k]), false)
                    ->assertSee(route("{$tagRoute}.destroy", $t[$k]), false);
            }
        }
    }

    public function test_kosong_tanpa_hitungan_perlu_dirapikan(): void
    {
        ExtrasCategory::create(['nama' => 'Berhijab', 'grup' => 'Tipe']);

        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.akun.extras'))->assertOk()
            ->assertDontSee('perlu dirapikan')
            ->assertSee('data-rapikan-tag', false);
    }

    public function test_aksi_dialog_json_pakai_logika_lama_dan_tercatat(): void
    {
        $t = $this->data();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patchJson(route('admin.tags.update', $t['lainnya']), ['grup' => 'Tipe'])
            ->assertOk()->assertJson(['ok' => true, 'pesan' => '#hijab dipindah ke grup Tipe.']);
        $this->assertSame('Tipe', $t['lainnya']->fresh()->grup);
        $this->actingAs($admin)->patchJson(route('admin.tags.update', $t['lainnya']), ['grup' => 'Ngawur'])->assertUnprocessable();

        $this->actingAs($admin)->postJson(route('admin.tags.gabung', $t['lainnya']), ['tujuan' => 'berhijab'])
            ->assertOk()->assertJson(['ok' => true]);
        $this->assertNull(ExtrasCategory::find($t['lainnya']->id));
        $this->assertSame(2, $t['bawaan']->extrasProfiles()->count());
        $this->actingAs($admin)->postJson(route('admin.tags.gabung', $t['typo']), ['tujuan' => 'tidak-ada'])
            ->assertUnprocessable()->assertJson(['ok' => false]);

        $this->actingAs($admin)->deleteJson(route('admin.tags.destroy', $t['typo']))
            ->assertOk()->assertJson(['ok' => true, 'pesan' => '#Nyetir motr dihapus.']);
        $this->assertNull(ExtrasCategory::find($t['typo']->id));
        $this->assertDatabaseMissing('extras_category_extras_profile', ['extras_category_id' => $t['typo']->id]);

        $this->assertSame(['TAG_UBAH_GRUP', 'TAG_GABUNG', 'TAG_HAPUS'], ActivityLog::whereIn('action', ['TAG_UBAH_GRUP', 'TAG_GABUNG', 'TAG_HAPUS'])->orderBy('id')->pluck('action')->all());
        $this->assertStringContainsString('menghapus #Nyetir motr (1 Extras)', ActivityLog::where('action', 'TAG_HAPUS')->value('description'));
    }

    public function test_fallback_form_biasa_redirect_back_dan_otorisasi(): void
    {
        $t = $this->data();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->from(route('admin.akun.extras'))->delete(route('admin.tags.destroy', $t['typo']))
            ->assertRedirect(route('admin.akun.extras'))->assertSessionHas('status');
        $this->actingAs(User::factory()->create(['role' => 'korlap']))->deleteJson(route('admin.tags.destroy', $t['rapi']))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'extras']))->deleteJson(route('admin.tags.destroy', $t['rapi']))->assertForbidden();
        $this->assertNotNull($t['rapi']->fresh());

        $this->actingAs($admin)->get('/admin/tag')->assertRedirect('/admin/akun/extras');
    }

    /** CM.3.3 (temuan reviewer B2): SA tanpa mode tetap bisa rapikan tag & ubah kategori Extras lewat route super-admin.*, bukan 403. */
    public function test_super_admin_tanpa_mode_bisa_rapikan_tag_dan_kategori(): void
    {
        $t = $this->data();
        $sa = User::factory()->create(['role' => 'super_admin']);
        $extras = $this->profil();

        $this->actingAs($sa)->patch(route('super-admin.tags.update', $t['lainnya']), ['grup' => 'Tipe'])->assertSessionHas('status');
        $this->assertSame('Tipe', $t['lainnya']->fresh()->grup);

        $this->actingAs($sa)->post(route('super-admin.tags.gabung', $t['typo']), ['tujuan' => 'silat'])->assertSessionHas('status');
        $this->assertNull(ExtrasCategory::find($t['typo']->id));

        $this->actingAs($sa)->delete(route('super-admin.tags.destroy', $t['rapi']))->assertSessionHas('status');

        $this->actingAs($sa)->patch(route('super-admin.admins.kategori', $extras->user), ['tag_nama' => ['Berhijab']])
            ->assertRedirect();
    }
}
