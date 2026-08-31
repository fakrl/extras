<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function buatAplikasi(): ProjectApplication
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias Test']);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Test',
            'client_ph' => 'PH Test',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $project->shootingDates()->create(['tanggal' => now()->addDays(10)]);

        return ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'deal',
        ]);
    }

    public function test_korlap_bisa_akses_halaman_absensi(): void
    {
        $korlap = User::factory()->create(['role' => 'admin_korlap']);
        $this->buatAplikasi();

        $response = $this->actingAs($korlap)->get(route('admin.attendance.index'));

        $response->assertOk();
    }

    public function test_admin_default_bisa_akses_halaman_absensi(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $this->buatAplikasi();

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();
    }

    public function test_korlap_bisa_tandai_hadir(): void
    {
        $korlap = User::factory()->create(['role' => 'admin_korlap']);
        $application = $this->buatAplikasi();
        $shootingDate = $application->castingProject->shootingDates->first();

        $response = $this->actingAs($korlap)->post(route('admin.attendance.store', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
            'dicatat_oleh' => $korlap->id,
        ]);
    }

    public function test_korlap_bisa_tandai_tidak_hadir(): void
    {
        $korlap = User::factory()->create(['role' => 'admin_korlap']);
        $application = $this->buatAplikasi();
        $shootingDate = $application->castingProject->shootingDates->first();

        $response = $this->actingAs($korlap)->post(route('admin.attendance.store', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'tidak_hadir',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'tidak_hadir',
        ]);
    }

    public function test_korlap_bisa_submit_catatan_lapangan_dari_halaman_absensi(): void
    {
        $korlap = User::factory()->create(['role' => 'admin_korlap']);
        $application = $this->buatAplikasi();

        $response = $this->actingAs($korlap)->post(route('admin.applications.catatan', $application), [
            'jenis' => 'catatan',
            'isi' => 'Datang tepat waktu.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('field_notes', [
            'project_application_id' => $application->id,
            'korlap_id' => $korlap->id,
        ]);
    }

    #[DataProvider('peranTanpaAksesProvider')]
    public function test_role_lain_ditolak_403_di_halaman(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->buatAplikasi();

        $response = $this->actingAs($user)->get(route('admin.attendance.index'));

        $response->assertStatus(403);
    }

    #[DataProvider('peranTanpaAksesProvider')]
    public function test_role_lain_ditolak_403_saat_tandai_absen(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $application = $this->buatAplikasi();
        $shootingDate = $application->castingProject->shootingDates->first();

        $response = $this->actingAs($user)->post(route('admin.attendance.store', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
        ]);

        $response->assertStatus(403);
    }

    public static function peranTanpaAksesProvider(): array
    {
        return [
            ['extras'],
            ['casting_director'],
            ['admin_talco'],
            ['admin_sosmed'],
        ];
    }

    public function test_submit_absen_dua_kali_update_bukan_duplikat(): void
    {
        $korlap = User::factory()->create(['role' => 'admin_korlap']);
        $application = $this->buatAplikasi();
        $shootingDate = $application->castingProject->shootingDates->first();

        $this->actingAs($korlap)->post(route('admin.attendance.store', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
        ]);

        $this->actingAs($korlap)->post(route('admin.attendance.store', $application), [
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'tidak_hadir',
            'catatan' => 'Sakit, izin.',
        ]);

        $this->assertSame(1, Attendance::where('project_application_id', $application->id)
            ->where('event_shooting_date_id', $shootingDate->id)
            ->count());

        $this->assertDatabaseHas('attendances', [
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'tidak_hadir',
            'catatan' => 'Sakit, izin.',
        ]);
    }

    public function test_unique_constraint_mencegah_duplikat_langsung_di_db(): void
    {
        $korlap = User::factory()->create(['role' => 'admin_korlap']);
        $application = $this->buatAplikasi();
        $shootingDate = $application->castingProject->shootingDates->first();

        Attendance::create([
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'hadir',
            'dicatat_oleh' => $korlap->id,
        ]);

        $this->expectException(QueryException::class);

        Attendance::create([
            'project_application_id' => $application->id,
            'event_shooting_date_id' => $shootingDate->id,
            'status' => 'tidak_hadir',
            'dicatat_oleh' => $korlap->id,
        ]);
    }
}
