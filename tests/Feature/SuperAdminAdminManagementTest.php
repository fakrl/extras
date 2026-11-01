<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-57: Super Admin kelola akun Admin/Client/sesama Super Admin
 * (nonaktifkan/aktifkan + hapus permanen).
 */
class SuperAdminAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public static function targetRoleProvider(): array
    {
        return [
            ['admin'], ['client'], ['super_admin'],
        ];
    }

    #[DataProvider('targetRoleProvider')]
    public function test_toggle_status_berhasil(string $role): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => $role, 'status' => 'aktif']);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.admins.toggle-status', $target))
            ->assertRedirect();

        $this->assertSame('nonaktif', $target->fresh()->status);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.admins.toggle-status', $target))
            ->assertRedirect();

        $this->assertSame('aktif', $target->fresh()->status);
    }

    public function test_soft_delete_dan_restore_berhasil(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin', 'status' => 'aktif']);

        $this->actingAs($superAdmin)
            ->from(route('super-admin.akun.index'))
            ->delete(route('super-admin.admins.destroy', $target))
            ->assertRedirect(route('super-admin.akun.index'));

        $this->assertNull(User::find($target->id));
        $this->assertNotNull(User::withTrashed()->find($target->id));
        $this->assertSame('nonaktif', $target->fresh()->status);

        $this->actingAs($superAdmin)
            ->from(route('super-admin.akun.index'))
            ->patch(route('super-admin.admins.restore', $target->id))
            ->assertRedirect(route('super-admin.akun.index'));

        $this->assertNotNull(User::find($target->id));
        $this->assertSame('aktif', $target->fresh()->status);
    }

    public function test_soft_delete_tetap_berhasil_untuk_akun_berhistori(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin']);
        CastingProject::create([
            'admin_id' => $target->id, 'nama_produksi' => 'P',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('super-admin.admins.destroy', $target));

        $response->assertRedirect();
        $this->assertNull(User::find($target->id));
        $this->assertNotNull(User::withTrashed()->find($target->id));
    }

    public function test_index_menandai_has_history_untuk_histori_di_luar_casting_projects(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin']);

        $target->kabari('Shooting Besok', 'Pengingat', jenis: 'reminder_h1');

        $response = $this->actingAs($superAdmin)->get(route('super-admin.akun.index'));

        $response->assertOk();
        $this->assertNotNull(User::find($target->id));
    }

    public function test_toggle_dan_hapus_akun_protected_ditolak(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $protected = User::factory()->create(['role' => 'super_admin', 'is_protected' => true]);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.admins.toggle-status', $protected))
            ->assertForbidden();
        $this->assertSame('aktif', $protected->fresh()->status);

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.admins.destroy', $protected))
            ->assertForbidden();
        $this->assertNotNull(User::find($protected->id));
    }

    public function test_super_admin_tidak_bisa_aksi_ke_akun_sendiri(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.admins.toggle-status', $superAdmin))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.admins.destroy', $superAdmin))
            ->assertForbidden();

        $this->assertNotNull(User::find($superAdmin->id));
    }

    public function test_akun_dinonaktifkan_tidak_bisa_login(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);

        $this->actingAs($superAdmin)->patch(route('super-admin.admins.toggle-status', $target));
        $this->post('/logout');

        $response = $this->from('/login')->post('/login', ['email' => $target->email, 'password' => 'password']);

        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }

    public function test_akun_protected_bisa_bikin_super_admin_baru_tanpa_admin_profile(): void
    {
        $protected = User::factory()->create(['role' => 'super_admin', 'is_protected' => true]);

        $response = $this->actingAs($protected)->post(route('super-admin.admins.store'), [
            'name' => 'Super Admin Baru',
            'email' => 'sa-baru@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);

        $response->assertRedirect(route('super-admin.akun.index'));

        $newUser = User::where('email', 'sa-baru@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('super_admin', $newUser->role);
        $this->assertNull($newUser->honor_nominal);
    }

    public function test_tambah_staf_pakai_password_sementara_wajib_ganti_dan_kredensial(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->post(route('super-admin.admins.store'), [
            'name' => 'Staf Baru',
            'email' => 'staf-baru@example.com',
            'role' => 'admin',
        ])->assertRedirect(route('super-admin.akun.index'))
            ->assertSessionHas('kredensial', fn ($k) => $k['username'] === 'staf-baru@example.com' && strlen($k['password']) >= 8);

        $staf = User::where('email', 'staf-baru@example.com')->first();
        $this->assertTrue((bool) $staf->wajib_ganti_password);
        $this->assertDatabaseHas('activity_logs', ['action' => 'CREATE_USER']);
    }

    public function test_akun_super_admin_biasa_gagal_bikin_super_admin_baru(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_protected' => false]);
        $countBefore = User::count();

        $response = $this->actingAs($superAdmin)->post(route('super-admin.admins.store'), [
            'name' => 'Super Admin Ilegal',
            'email' => 'sa-ilegal@example.com',
            'password' => 'password123',
            'role' => 'super_admin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame($countBefore, User::count());
        $this->assertTrue(User::where('email', 'sa-ilegal@example.com')->doesntExist());
    }

    public function test_index_menampilkan_modal_tambah_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.akun.index'));

        $response->assertOk();
        $response->assertSee('add-admin-dialog', false);
        $response->assertSee('action="'.route('super-admin.admins.store').'"', false);
    }

    public function test_super_admin_bisa_update_profil_dan_role_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_protected' => true]);
        $target = User::factory()->create(['role' => 'admin', 'name' => 'Nama Lama', 'email' => 'lama@example.com']);

        $response = $this->actingAs($superAdmin)->patch(route('super-admin.admins.update', $target), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'role' => 'korlap',
            'konfirmasi_role' => 1,
        ]);

        $response->assertRedirect(route('super-admin.akun.index'));
        $target->refresh();
        $this->assertSame('Nama Baru', $target->name);
        $this->assertSame('baru@example.com', $target->email);
        $this->assertSame('korlap', $target->role);
    }

    public function test_super_admin_biasa_tidak_bisa_ubah_role_menjadi_super_admin(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_protected' => false]);
        $target = User::factory()->create(['role' => 'admin', 'name' => 'Nama Staf', 'email' => 'staf@example.com']);

        $response = $this->actingAs($superAdmin)->patch(route('super-admin.admins.update', $target), [
            'name' => 'Nama Staf Edit',
            'email' => 'staf@example.com',
            'role' => 'super_admin',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame('admin', $target->fresh()->role);
    }

    public function test_index_dengan_filter_pencarian(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $user1 = User::factory()->create(['role' => 'admin', 'name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        $user2 = User::factory()->create(['role' => 'korlap', 'name' => 'Siti Rahma', 'email' => 'siti@test.com']);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.akun.index', ['q' => 'Budi']));
        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertDontSee('Siti Rahma');

        $responseRole = $this->actingAs($superAdmin)->get(route('super-admin.akun.index', ['role' => 'korlap']));
        $responseRole->assertOk();
        $responseRole->assertSee('Siti Rahma');
    }

    public static function bukanSuperAdminProvider(): array
    {
        return [
            ['admin'], ['korlap'], ['client'], ['extras'],
        ];
    }

    #[DataProvider('bukanSuperAdminProvider')]
    public function test_role_selain_super_admin_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $target = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->patch(route('super-admin.admins.toggle-status', $target))->assertForbidden();
        $this->actingAs($user)->delete(route('super-admin.admins.destroy', $target))->assertForbidden();
        $this->actingAs($user)->patch(route('super-admin.admins.update', $target), [
            'name' => 'Test', 'email' => 'test@example.com', 'role' => 'admin',
        ])->assertForbidden();
    }

    public function test_super_admin_bisa_update_grade_dan_tag_extras(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras']);
        $extras->extrasProfile()->create();

        $response = $this->actingAs($superAdmin)->patch(route('super-admin.admins.extras-update', $extras), [
            'grade' => 'A',
            'tag_nama' => ['Karakter Unik', 'Fotogenik'],
        ]);

        $response->assertSessionHas('status');
        $this->assertSame('A', $extras->fresh()->extrasProfile->grade_saat_ini);
        $this->assertCount(2, $extras->fresh()->extrasProfile->categories);
    }

    public function test_update_akun_umum_menolak_target_extras(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $extras = User::factory()->create(['role' => 'extras']);

        $response = $this->actingAs($superAdmin)->patch(route('super-admin.admins.update', $extras), [
            'name' => 'Ganti Nama',
            'email' => 'ganti@example.com',
            'role' => 'admin',
        ]);

        $response->assertSessionHas('error');
        $this->assertSame('extras', $extras->fresh()->role);
    }

    public function test_super_admin_bisa_update_username_dan_honor_staf(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $korlap = User::factory()->create([
            'role' => 'korlap',
            'username' => 'korlap_lama',
            'honor_nominal' => 150000,
        ]);

        $response = $this->actingAs($superAdmin)->patch(route('super-admin.admins.update', $korlap), [
            'name' => 'Korlap Update',
            'username' => 'korlap_baru',
            'email' => $korlap->email,
            'role' => 'korlap',
            'honor_nominal' => 250000,
        ]);

        $response->assertSessionHas('status');
        $fresh = $korlap->fresh();
        $this->assertSame('Korlap Update', $fresh->name);
        $this->assertSame('korlap_baru', $fresh->username);
        $this->assertEquals(250000, $fresh->honor_nominal);
    }

    public function test_staf_dengan_username_bisa_login_pakai_username(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'username' => 'admin_super_kece',
            'email' => 'adminkece@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin_super_kece', // Identifier input name="email" di form login
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }
}
