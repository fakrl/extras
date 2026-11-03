<?php

namespace Tests\Feature;

use App\Models\CastingProject;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit item 4: ganti PIC/Korlap lewat modal "Pengelola" wajib langsung
 * mencabut akses `dikelolaOleh` orang lama, tanpa menghapus riwayat
 * StaffPayroll yang sudah terlanjur dibuat untuk penugasan lama itu.
 */
class RevokasiPengelolaProyekTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_lama_kehilangan_akses_setelah_diganti_pic_baru_dapat_akses(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $picLama = User::factory()->create(['role' => 'admin']);
        $picBaru = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $picLama->id]);
        $project->adminAssignments()->create(['user_id' => $picLama->id, 'assigned_by' => $sa->id, 'status_log' => 'berjalan']);

        $this->actingAs($sa)->patch(route('admin.projects.pengelola', $project), [
            'admin_id' => $picBaru->id,
        ])->assertRedirect();

        $this->assertSame($picBaru->id, $project->fresh()->admin_id);

        $this->actingAs($picLama)->patch(route('admin.projects.pengelola', $project), [
            'admin_id' => $picLama->id,
        ])->assertForbidden();

        $this->actingAs($picBaru)->patch(route('admin.projects.pengelola', $project), [
            'admin_id' => $picBaru->id,
        ])->assertRedirect()->assertSessionHas('status', 'Tidak ada perubahan pengelola.');
    }

    public function test_korlap_diganti_payroll_lama_tidak_terhapus_dan_korlap_lama_kehilangan_akses(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $pic = User::factory()->create(['role' => 'admin']);
        $korlapLama = User::factory()->create(['role' => 'korlap']);
        $korlapBaru = User::factory()->create(['role' => 'korlap']);
        $project = CastingProject::factory()->create(['admin_id' => $pic->id]);

        $assignment = $project->adminAssignments()->create(['user_id' => $korlapLama->id, 'assigned_by' => $sa->id, 'status_log' => 'berjalan']);
        $payroll = $assignment->tandaiSelesai();
        $payroll->tandaiDibayar();
        $payrollId = $payroll->id;
        $nominal = $payroll->nominal_pokok;

        $this->actingAs($pic)->patch(route('admin.projects.pengelola', $project), [
            'korlap_id' => $korlapBaru->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('staff_payrolls', ['id' => $payrollId, 'nominal_pokok' => $nominal]);
        $this->assertTrue($payroll->fresh()->isDibayar());
        $this->assertSame($korlapBaru->id, $project->fresh()->korlap()?->id);

        // Rute callsheet digerbang langsung oleh dikelolaOleh() - korlapLama harus 403 sekarang.
        $tanggal = $project->shootingDates()->create(['tanggal' => today()->addDays(3)]);
        $this->actingAs($korlapLama)->get(route('admin.callsheet.show', [$project, $tanggal->tanggal->toDateString()]))->assertForbidden();
        $this->actingAs($korlapBaru)->get(route('admin.callsheet.show', [$project, $tanggal->tanggal->toDateString()]))->assertOk();
    }

    public function test_pic_lama_bisa_diangkat_lagi_tanpa_terhalang_baris_lama(): void
    {
        $sa = User::factory()->create(['role' => 'super_admin']);
        $picA = User::factory()->create(['role' => 'admin']);
        $picB = User::factory()->create(['role' => 'admin']);
        $project = CastingProject::factory()->create(['admin_id' => $picA->id]);
        $project->adminAssignments()->create(['user_id' => $picA->id, 'assigned_by' => $sa->id, 'status_log' => 'berjalan']);

        $this->actingAs($sa)->patch(route('admin.projects.pengelola', $project), ['admin_id' => $picB->id])->assertRedirect();
        $this->actingAs($picA)->patch(route('admin.projects.pengelola', $project), ['admin_id' => $picA->id])->assertForbidden();

        $this->actingAs($sa)->patch(route('admin.projects.pengelola', $project), ['admin_id' => $picA->id])->assertRedirect();
        $this->assertSame($picA->id, $project->fresh()->admin_id);
        $this->actingAs($picA)->patch(route('admin.projects.pengelola', $project), ['admin_id' => $picA->id])
            ->assertRedirect()->assertSessionHas('status', 'Tidak ada perubahan pengelola.');
    }
}
