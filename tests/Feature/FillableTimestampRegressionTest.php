<?php

namespace Tests\Feature;

use App\Models\AdminProfile;
use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regresi audit full-codebase (30 Agu 2026): 6 model menulis kolom
 * *_at lewat update() tanpa kolom itu ada di #[Fillable] — sama persis
 * pola bug yang sudah 3x kejadian (User, FeeNegotiation, ExtrasProfile).
 * Tanpa fix ini, tiap method di bawah lempar MassAssignmentException
 * (preventSilentlyDiscardingAttributes aktif di non-production).
 */
class FillableTimestampRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_tandai_ditransfer_tidak_lempar_exception(): void
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias']);
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $application = ProjectApplication::create([
            'casting_project_id' => $project->id, 'extras_id' => $extras->id,
            'status_partisipasi' => 'lolos',
        ]);
        $payment = $application->payment()->create(['status' => 'belum_dibayar']);

        $payment->tandaiDitransfer('payments/bukti-transfer/test.jpg');

        $this->assertSame('ditransfer', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->ditransfer_at);
    }

    public function test_payment_konfirmasi_diterima_tidak_lempar_exception(): void
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias']);
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $application = ProjectApplication::create([
            'casting_project_id' => $project->id, 'extras_id' => $extras->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
        ]);
        $payment = $application->payment()->create(['status' => 'ditransfer']);

        $payment->konfirmasiDiterima();

        $this->assertSame('dikonfirmasi_diterima', $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->dikonfirmasi_at);
    }

    public function test_contract_sign_menyimpan_signed_at_tanpa_exception(): void
    {
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id, 'alias' => 'Alias']);
        $admin = User::factory()->create(['role' => 'admin_default']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $application = ProjectApplication::create([
            'casting_project_id' => $project->id, 'extras_id' => $extras->id,
            'status_partisipasi' => 'kontrak_ditandatangani',
        ]);
        $contract = $application->contract()->create([
            'ttd_admin_signature_path' => 'contracts/signatures/admin.png',
            'ttd_extras_signature_path' => 'contracts/signatures/extras.png',
        ]);

        $contract->update(['signed_at' => now()]);

        $this->assertNotNull($contract->fresh()->signed_at);
    }

    public function test_admin_project_assignment_tandai_selesai_tidak_lempar_exception(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin_korlap']);
        AdminProfile::create(['user_id' => $admin->id, 'honor_nominal' => 500000, 'created_by' => $superAdmin->id]);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $assignment = AdminProjectAssignment::create([
            'casting_project_id' => $project->id, 'user_id' => $admin->id,
            'assigned_by' => $superAdmin->id, 'status_log' => 'berjalan',
        ]);

        $payroll = $assignment->tandaiSelesai();

        $this->assertSame('selesai', $assignment->fresh()->status_log);
        $this->assertNotNull($assignment->fresh()->completed_at);
        $this->assertInstanceOf(StaffPayroll::class, $payroll);
    }

    public function test_staff_payroll_tandai_slip_dibuat_tidak_lempar_exception(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin_korlap']);
        $project = CastingProject::create([
            'admin_id' => $admin->id, 'nama_produksi' => 'P', 'client_ph' => 'PH',
            'deadline' => now()->addDays(7), 'kuota' => 5,
        ]);
        $assignment = AdminProjectAssignment::create([
            'casting_project_id' => $project->id, 'user_id' => $admin->id,
            'assigned_by' => $superAdmin->id, 'status_log' => 'selesai',
        ]);
        $payroll = $assignment->payroll()->create(['nominal_pokok' => 500000]);

        $payroll->tandaiSlipDibuat('payrolls/slip-test.pdf');

        $this->assertNotNull($payroll->fresh()->generated_at);
        $this->assertSame('payrolls/slip-test.pdf', $payroll->fresh()->pdf_slip_path);
    }

    public function test_admin_profile_update_honor_tidak_lempar_exception(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin_korlap']);
        $profile = AdminProfile::create(['user_id' => $admin->id, 'honor_nominal' => 500000, 'created_by' => $superAdmin->id]);

        $profile->updateHonor(750000);

        $this->assertEquals(750000, $profile->fresh()->honor_nominal);
        $this->assertNotNull($profile->fresh()->honor_updated_at);
    }
}
