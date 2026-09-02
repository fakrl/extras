<?php

namespace Tests\Feature;

use App\Models\AdminProfile;
use App\Models\CastingProject;
use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * RF-57: Super Admin kelola akun Admin/CD/sesama Super Admin
 * (nonaktifkan/aktifkan + hapus permanen).
 */
class SuperAdminAdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public static function targetRoleProvider(): array
    {
        return [
            ['admin_default'], ['casting_director'], ['super_admin'],
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

    public function test_hapus_permanen_berhasil_untuk_akun_bersih(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($superAdmin)
            ->delete(route('super-admin.admins.destroy', $target))
            ->assertRedirect(route('super-admin.admins.index'));

        $this->assertNull(User::find($target->id));
    }

    public function test_hapus_permanen_ditolak_untuk_akun_berhistori(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin_default']);
        CastingProject::create([
            'admin_id' => $target->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('super-admin.admins.destroy', $target));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Akun ini punya riwayat penugasan, nonaktifkan saja.');
        $this->assertNotNull(User::find($target->id));
    }

    public function test_index_menandai_has_history_untuk_histori_di_luar_casting_projects(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $target = User::factory()->create(['role' => 'admin_default']);

        NotificationLog::create([
            'user_id' => $target->id,
            'channel' => 'email',
            'jenis' => 'reminder_h1',
            'status' => 'terkirim',
            'sent_at' => now(),
        ]);

        $response = $this->actingAs($superAdmin)->get(route('super-admin.admins.index'));

        $response->assertOk();
        $response->assertDontSee('delete-dialog-'.$target->id, false);
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
        $target = User::factory()->create(['role' => 'admin_default', 'password' => bcrypt('password')]);

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

        $response->assertRedirect(route('super-admin.admins.index'));

        $newUser = User::where('email', 'sa-baru@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertSame('super_admin', $newUser->role);
        $this->assertTrue(AdminProfile::where('user_id', $newUser->id)->doesntExist());
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

        $response = $this->actingAs($superAdmin)->get(route('super-admin.admins.index'));

        $response->assertOk();
        $response->assertSee('add-admin-dialog', false);
        $response->assertSee('action="'.route('super-admin.admins.store').'"', false);
    }

    public static function bukanSuperAdminProvider(): array
    {
        return [
            ['admin_default'], ['admin_talco'], ['admin_korlap'], ['admin_sosmed'],
            ['casting_director'], ['extras'],
        ];
    }

    #[DataProvider('bukanSuperAdminProvider')]
    public function test_role_selain_super_admin_ditolak(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $target = User::factory()->create(['role' => 'admin_default']);

        $this->actingAs($user)->patch(route('super-admin.admins.toggle-status', $target))->assertForbidden();
        $this->actingAs($user)->delete(route('super-admin.admins.destroy', $target))->assertForbidden();
    }
}
