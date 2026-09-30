<?php

namespace Tests\Feature;

use App\Models\ExtrasProfile;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BoWaNormalisasiTest extends TestCase
{
    use RefreshDatabase;

    public static function nomorProvider(): array
    {
        return [
            ['0812-3456-789', '628123456789'],
            ['+62 812 3456789', '628123456789'],
            ['812345678', '62812345678'],
            ['6281234567890', '6281234567890'],
            ['', null],
            ['0811', null],
            ['abc', null],
            ['1234567890', null],
        ];
    }

    #[DataProvider('nomorProvider')]
    public function test_normalisasi(string $input, ?string $hasil): void
    {
        $this->assertSame($hasil, User::normalisasiWa($input));
    }

    public function test_nomor_wa_internasional(): void
    {
        $user = User::factory()->make(['nomor_wa' => '0812 3456 789']);
        $this->assertSame('628123456789', $user->nomorWaInternasional());
    }

    public function test_kirim_menormalisasi_nomor_ke_node(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true])]);

        $this->assertTrue(app(WhatsAppService::class)->kirim('+62 812-3456-789', 'halo'));
        Http::assertSent(fn (Request $r) => $r['nomor'] === '628123456789');
    }

    public function test_form_menolak_nomor_ngaco(): void
    {
        $user = User::factory()->create(['role' => 'extras']);
        ExtrasProfile::create(['user_id' => $user->id]);

        $this->actingAs($user)->put('/extras/profil', ['nama_asli' => 'X', 'username' => 'user_x', 'nomor_wa' => '12-34'])
            ->assertSessionHasErrors(['nomor_wa' => 'Nomor WhatsApp tidak valid. Contoh: 0812-3456-7890 atau +62 812 3456 7890.']);

        $client = User::factory()->create(['role' => 'client']);
        $this->actingAs($client)->put(route('client.profil.update'), ['name' => 'C', 'nomor_wa' => 'nomorku'])
            ->assertSessionHasErrors('nomor_wa');

        $sa = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($sa)->post(route('super-admin.clients.store'), ['name' => 'C', 'username' => 'client_c', 'nomor_wa' => '0811'])
            ->assertSessionHasErrors('nomor_wa');
    }

    public function test_wa_tes_sukses(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => true])]);

        $this->artisan('wa:tes', ['nomor' => '0812-3456-789', 'pesan' => 'halo'])
            ->expectsOutput('Terkirim.')->assertSuccessful();
        Http::assertSent(fn (Request $r) => $r['nomor'] === '628123456789' && $r['pesan'] === 'halo');
    }

    public function test_wa_tes_401_token_salah(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => false, 'pesan' => 'Token tidak valid.'], 401)]);

        $this->artisan('wa:tes', ['nomor' => '081234567890'])
            ->expectsOutputToContain('token salah')->assertFailed();
    }

    public function test_wa_tes_503_belum_scan(): void
    {
        Http::fake(['*/send' => Http::response(['sukses' => false, 'pesan' => 'WhatsApp client belum siap.'], 503)]);

        $this->artisan('wa:tes', ['nomor' => '081234567890'])
            ->expectsOutputToContain('scan QR')->assertFailed();
    }

    public function test_wa_tes_nomor_ngaco(): void
    {
        Http::fake();

        $this->artisan('wa:tes', ['nomor' => '123'])->expectsOutputToContain('tidak valid')->assertFailed();
        Http::assertNothingSent();
    }
}
