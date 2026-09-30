<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BjDashboardExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function extras(bool $lengkap = true): User
    {
        $u = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $u->id, 'foto_profil_path' => $lengkap ? 'x.jpg' : null, 'usia' => 25, 'gender' => 'Wanita', 'tinggi_badan' => 160]);

        return $u;
    }

    private function proyek(string $nama, int $hari, array $tag = []): CastingProject
    {
        $p = CastingProject::create([
            'admin_id' => User::factory()->create(['role' => 'admin'])->id,
            'nama_produksi' => $nama, 'deadline' => now()->addDays($hari), 'kuota' => 10, 'status' => 'dibuka',
        ]);
        $p->classes()->create(['nama_kelas' => 'Peran '.$nama, 'budget_client' => 1, 'kuota_kelas' => 3])->categories()->sync($tag);

        return $p;
    }

    private function urutan(string $html, array $section): void
    {
        $pos = array_map(fn ($s) => strpos($html, 'data-dash="'.$s.'"'), $section);
        $this->assertNotContains(false, $pos);
        $sorted = $pos;
        sort($sorted);
        $this->assertSame($sorted, $pos);
    }

    public function test_tanpa_tindakan_section_perlu_tindakan_tidak_dirender(): void
    {
        $html = $this->actingAs($this->extras())->get(route('extras.dashboard'))->assertOk()
            ->assertDontSee('data-dash="perlu-tindakan"', false)
            ->assertDontSee('Semua aman')
            ->assertDontSee('Lihat Profil Saya')->assertDontSee('Lihat Casting Call')
            ->assertSee('Belum ada lowongan terbuka. Nanti kami kabari kalau ada yang baru.')
            ->getContent();

        $this->urutan($html, ['pendaftaran', 'casting-call', 'status-talenta', 'jadwal']);
    }

    public function test_dengan_tindakan_perlu_tindakan_paling_atas_dan_urutan_1_sampai_6(): void
    {
        $this->proyek('Iklan Kopi', 5);

        $html = $this->actingAs($this->extras(false))->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Profil belum lengkap')->getContent();

        $this->urutan($html, ['perlu-tindakan', 'pendaftaran', 'casting-call', 'status-talenta', 'jadwal']);
    }

    public function test_maks_5_lowongan_paling_cocok_dulu_dengan_badge(): void
    {
        [$a, $b] = collect(['Dewasa', 'Naik motor'])->map(fn ($n) => ExtrasCategory::create(['nama' => $n])->id)->all();
        $user = $this->extras();
        $user->extrasProfile->categories()->sync([$a]);

        foreach (range(1, 5) as $i) {
            $this->proyek("Tanpa Tag $i", $i);
        }
        $this->proyek('Setengah Cocok', 20, [$a, $b]);
        $this->proyek('Pas Banget', 30, [$a]);

        $html = $this->actingAs($user)->get(route('extras.dashboard'))->assertOk()
            ->assertSee('Lihat semua')
            ->assertSee('100% cocok')->assertSee('50% cocok')
            ->assertSee('Sisa 3 dari 3')
            ->assertSeeInOrder(['Pas Banget', 'Setengah Cocok', 'Tanpa Tag 1', 'Tanpa Tag 2', 'Tanpa Tag 3'])
            ->assertDontSee('Tanpa Tag 4')
            ->getContent();

        $this->assertSame(5, substr_count($html, '>Daftar</a>'));
        $this->assertSame(2, substr_count($html, '% cocok'));
    }

    public function test_lowongan_yang_sudah_didaftar_tidak_muncul(): void
    {
        $user = $this->extras();
        $p = $this->proyek('Sudah Daftar', 5);
        $this->proyek('Belum Daftar', 6);
        $p->applications()->create(['extras_id' => $user->extrasProfile->id, 'casting_project_class_id' => $p->classes->first()->id, 'status_partisipasi' => 'diajukan']);

        $html = $this->actingAs($user)->get(route('extras.dashboard'))->assertOk()->getContent();
        $casting = substr($html, strpos($html, 'data-dash="casting-call"'), strpos($html, 'data-dash="status-talenta"') - strpos($html, 'data-dash="casting-call"'));

        $this->assertStringContainsString('Belum Daftar', $casting);
        $this->assertStringNotContainsString('Sudah Daftar', $casting);
    }
}
