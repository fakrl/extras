<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CV.4: Tambah staf langsung dari dialog Setujui/Tetapkan PIC lewat fetch() JSON. */
class CvTambahStafDialogTest extends TestCase
{
    use RefreshDatabase;

    public function test_sa_bisa_tambah_admin_lewat_fetch_dan_dapat_kredensial(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $r = $this->actingAs($sa)->postJson(route('super-admin.admins.store'), [
            'name' => 'Admin Baru', 'email' => 'admin.baru@example.test', 'role' => 'admin',
        ]);

        $r->assertOk()->assertJsonStructure(['id', 'name', 'kredensial' => ['nama', 'username', 'password', 'url']]);
        $this->assertDatabaseHas('users', ['email' => 'admin.baru@example.test', 'role' => 'admin', 'wajib_ganti_password' => true]);
        $this->assertDatabaseHas('activity_logs', ['action' => 'CREATE_USER']);
    }

    public function test_non_sa_ditolak_403(): void
    {
        foreach (['admin', 'korlap', 'client', 'extras'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'aktif']);
            $this->actingAs($user)->postJson(route('super-admin.admins.store'), [
                'name' => 'X', 'email' => 'x@example.test', 'role' => 'admin',
            ])->assertForbidden();
        }
    }

    public function test_validasi_gagal_mengembalikan_422_tanpa_membuat_akun(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->postJson(route('super-admin.admins.store'), [
            'name' => '', 'email' => 'bukan-email', 'role' => 'admin',
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_link_tambah_staf_tampil_di_dialog_tetapkan_pic(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $klien = User::factory()->create(['role' => 'client']);
        $p = CastingProject::factory()->create([
            'admin_id' => null, 'client_id' => $klien->id, 'client_request_status' => 'disetujui',
        ]);

        $this->actingAs($sa)->get(route('super-admin.dashboard'))
            ->assertOk()->assertSee('+ Admin baru')->assertSee('+ Korlap baru');
    }

    /**
     * Regresi bug BLOCKING: input di form mini (tersembunyi via hidden) TIDAK BOLEH punya
     * `required` - spec HTML tetap memvalidasinya meski hidden, jadi form Setujui/Tetapkan PIC
     * (yang meng-include partial ini) diam-diam gagal checkValidity() dan tidak pernah submit.
     * Juga TIDAK BOLEH punya `name=` supaya tidak ikut ke-submit sebagai bagian form utama.
     */
    public function test_form_mini_tidak_punya_required_atau_name_di_html_hasil_render(): void
    {
        $html = view('partials.staf-baru-mini', [
            'uid' => 'stafbaru-x', 'selectAdmin' => 'admin-x', 'selectKorlap' => 'korlap-x',
        ])->render();

        preg_match_all('/<div[^>]*class="staf-mini-form[^"]*"[^>]*>(.*?)<div class="dash-dialog-btn">/s', $html, $m);
        $this->assertNotEmpty($m[1], 'tidak menemukan blok .staf-mini-form di HTML hasil render');

        foreach ($m[1] as $blok) {
            $this->assertStringNotContainsString('required', $blok);
            $this->assertDoesNotMatchRegularExpression('/\bname="(nama|email|nomor_wa)"/', $blok);
        }

        $this->assertStringContainsString('data-field="nama"', $html);
        $this->assertStringContainsString('data-field="email"', $html);
        $this->assertStringContainsString('data-field="nomor_wa"', $html);
    }
}
