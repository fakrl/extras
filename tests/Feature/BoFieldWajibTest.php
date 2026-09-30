<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BoFieldWajibTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_utama_pakai_bintang_wajib_tanpa_teks_opsional(): void
    {
        $extras = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::factory()->create(['user_id' => $extras->id]);

        $halaman = [
            [null, route('login')],
            [null, route('register')],
            [$extras, route('extras.profile.edit')],
            [User::factory()->create(['role' => 'admin']), route('admin.projects.create')],
            [User::factory()->create(['role' => 'client']), route('client.projects.request')],
        ];

        foreach ($halaman as [$user, $url]) {
            $user ? $this->actingAs($user) : auth()->logout();
            $this->get($url)->assertOk()
                ->assertSee('<span class="wajib" aria-hidden="true">*</span>', false)
                ->assertDontSee('(opsional)')
                ->assertDontSee('(Opsional');
        }
    }

    public function test_view_tidak_ada_teks_opsional(): void
    {
        $sisa = collect(File::allFiles(resource_path('views')))
            ->reject(fn ($f) => str_contains($f->getPathname(), 'privacy-policy'))
            ->filter(fn ($f) => stripos(preg_replace('/\{\{--.*?--\}\}/s', '', $f->getContents()), 'opsional') !== false)
            ->map->getRelativePathname()->values()->all();

        $this->assertSame([], $sisa);
    }
}
