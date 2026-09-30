<?php

namespace Tests\Feature;

use App\Http\Controllers\SuperAdmin\AdminManagementController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAkunTest extends TestCase
{
    use RefreshDatabase;

    private function buatClient(): array
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->from(route('super-admin.akun.index'))
            ->post(route('super-admin.clients.store'), [
                'name' => 'Andini',
                'nama_perusahaan' => 'PT Layar Senja',
                'username' => 'andini_ls',
                'nomor_wa' => '0812 3456 789',
            ])
            ->assertRedirect(route('super-admin.akun.index'))
            ->assertSessionHas('kredensial');

        $password = session('kredensial')['password'];
        auth()->logout();

        return [User::where('username', 'andini_ls')->first(), $password];
    }

    public function test_sa_bikin_client_tanpa_email_dan_client_dipaksa_ganti_password(): void
    {
        [$client, $password] = $this->buatClient();

        $this->assertSame('client', $client->role);
        $this->assertNull($client->email);
        $this->assertTrue($client->wajib_ganti_password);
        $this->assertSame('PT Layar Senja', $client->nama_perusahaan);
        $this->assertSame('628123456789', $client->nomor_wa);
        $this->assertSame(10, strlen($password));
        $this->assertDoesNotMatchRegularExpression('/[0Ool1I]/', $password);

        $this->post('/login', ['email' => 'andini_ls', 'password' => $password])->assertRedirect('/client/dashboard');
        $this->get('/client/dashboard')->assertRedirect(route('ubah-password'));
        $this->get(route('ubah-password'))->assertOk();

        $this->post(route('ubah-password.update'), [
            'current_password' => $password,
            'new_password' => 'passwordbaru123',
            'new_password_confirmation' => 'passwordbaru123',
        ])->assertRedirect('/client/dashboard');

        $this->assertFalse($client->fresh()->wajib_ganti_password);
        $this->get('/client/dashboard')->assertOk()->assertSee('Lengkapi profil');
        $this->get(route('client.profil'))->assertOk()->assertSee('PT Layar Senja');
    }

    public function test_dialog_kredensial_tampil_setelah_bikin_client(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($sa)->followingRedirects()->from(route('super-admin.akun.index'))
            ->post(route('super-admin.clients.store'), ['name' => 'Rudy', 'username' => 'rudy_kb'])
            ->assertOk()->assertSee('kredensial-dialog')->assertSee('rudy_kb');
    }

    public function test_non_sa_tidak_bisa_bikin_client(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('super-admin.clients.store'), ['name' => 'X', 'username' => 'x_ilegal'])
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['username' => 'x_ilegal']);
    }

    public function test_reset_password_set_flag_dan_kirim_kredensial(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $client = User::factory()->create(['role' => 'client', 'username' => 'cl']);

        $this->actingAs($sa)->post(route('super-admin.admins.reset-password', $client))
            ->assertSessionHas('kredensial', fn ($k) => $k['username'] === 'cl' && strlen($k['password']) === 10);

        $this->assertTrue($client->fresh()->wajib_ganti_password);
    }

    public function test_forgot_password_akun_tanpa_email_dapat_pesan_khusus(): void
    {
        User::factory()->create(['role' => 'client', 'username' => 'tanpa_email', 'email' => null]);

        $this->post(route('password.email'), ['email' => 'tanpa_email'])
            ->assertSessionHasErrors(['email' => 'Akun ini belum punya email. Hubungi Super Admin JBTB untuk reset password.']);

        $this->post(route('password.email'), ['email' => 'tidak_dikenal'])
            ->assertSessionHasNoErrors()->assertSessionHas('status');
    }

    public function test_client_bisa_update_profil(): void
    {
        $client = User::factory()->create(['role' => 'client', 'email' => null]);

        $this->actingAs($client)->put(route('client.profil.update'), [
            'name' => 'Nama Baru', 'nama_perusahaan' => 'PH Baru', 'email' => 'baru@ph.test', 'nomor_wa' => '0811-2233-4455',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame('baru@ph.test', $client->fresh()->email);
    }

    public function test_password_sementara_tanpa_karakter_mirip(): void
    {
        foreach (range(1, 50) as $_) {
            $this->assertMatchesRegularExpression('/^[a-km-np-zA-HJ-NP-Z2-9]{10}$/', AdminManagementController::passwordSementara());
        }
    }
}
