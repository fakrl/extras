<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BnFavoritTest extends TestCase
{
    use RefreshDatabase;

    private function extras(string $username, bool $favorit = false): ExtrasProfile
    {
        return ExtrasProfile::factory()->create([
            'user_id' => User::factory()->create(['role' => 'extras', 'username' => $username])->id,
            'apresiasi' => $favorit,
        ]);
    }

    public function test_bintang_toggle_satu_klik_admin_sa_dengan_log(): void
    {
        $ex = $this->extras('bintang_satu');

        foreach (['admin', 'super_admin'] as $role) {
            $aktor = User::factory()->create(['role' => $role]);
            $this->actingAs($aktor)->from('/admin/akun/extras')->patch(route('admin.extras.favorit', $ex->user_id))
                ->assertRedirect('/admin/akun/extras#fav-'.$ex->id);
            $this->assertTrue($ex->fresh()->apresiasi);

            $this->actingAs($aktor)->patch(route('admin.extras.favorit', $ex->user_id));
            $this->assertFalse($ex->fresh()->apresiasi);
        }

        $this->assertSame(4, ActivityLog::where('action', 'TOGGLE_EXTRAS_FAVORIT')->count());
        $this->assertStringContainsString('@bintang_satu', ActivityLog::where('action', 'TOGGLE_EXTRAS_FAVORIT')->first()->description);

        foreach (['korlap', 'client', 'extras'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->patch(route('admin.extras.favorit', $ex->user_id))->assertForbidden();
        }
        $this->assertFalse($ex->fresh()->apresiasi);
    }

    public function test_catatan_kenapa_favorit_dari_popup_profil(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $ex = $this->extras('pakai_catatan');

        $this->actingAs($admin)->get(route('admin.extras.profil', [$ex->user_id, 'partial' => 1]))
            ->assertOk()->assertSee('Kenapa favorit?')->assertSee('Jadikan Favorit');

        $this->actingAs($admin)->patch(route('admin.extras.favorit', $ex->user_id), ['apresiasi_catatan' => 'on time']);
        $this->assertSame('on time', $ex->fresh()->apresiasi_catatan);

        $this->actingAs($admin)->get(route('admin.extras.profil', [$ex->user_id, 'partial' => 1]))
            ->assertSee('Kenapa favorit? on time');
    }

    public function test_filter_dan_urut_favorit_di_lineup(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $p = CastingProject::factory()->create(['admin_id' => $admin->id]);
        $biasa = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $this->extras('biasa_aja')->id, 'status_partisipasi' => 'diajukan']);
        $fav = ProjectApplication::create(['casting_project_id' => $p->id, 'extras_id' => $this->extras('si_favorit', true)->id, 'status_partisipasi' => 'diajukan']);
        $biasa->forceFill(['created_at' => now()->addMinute()])->save();

        $this->actingAs($admin)->get(route('admin.projects.applicants', $p))->assertOk()
            ->assertSee('class="xcard-fav"', false)->assertSee('Favorit dulu');

        $ids = fn ($q) => $this->actingAs($admin)->get(route('admin.projects.applicants', [$p, ...$q]))->viewData('applicants')->pluck('id')->all();
        $this->assertSame([$fav->id], $ids(['favorit' => 1]));
        $this->assertSame([$biasa->id, $fav->id], $ids([]));
        $this->assertSame([$fav->id, $biasa->id], $ids(['urut' => 'favorit']));

        $this->actingAs($admin)->get(route('admin.projects.applicants', [$p, 'favorit' => 1]))->assertSee('fchip', false);
    }

    public function test_filter_dan_urut_favorit_di_manajemen_akun(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $fav = $this->extras('akun_favorit', true);
        $biasa = $this->extras('akun_biasa');
        $biasa->user->forceFill(['created_at' => now()->addMinute()])->save();

        $ids = fn ($q) => $this->actingAs($sa)->get(route('super-admin.akun.index', ['role' => 'extras', ...$q]))->viewData('users')->pluck('id')->all();
        $this->assertSame([$fav->user_id], $ids(['favorit' => 1]));
        $this->assertSame([$fav->user_id, $biasa->user_id], $ids(['urut' => 'favorit']));

        $this->actingAs($sa)->get(route('super-admin.akun.index', ['role' => 'extras', 'favorit' => 1]))
            ->assertSee('⭐ Favorit')->assertSee('class="xcard-fav"', false)->assertSee('class="is-on"', false);
    }
}
