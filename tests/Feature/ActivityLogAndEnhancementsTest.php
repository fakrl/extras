<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AdminProfile;
use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ExtrasProfile;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivityLogAndEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fee_negotiation_saves_and_displays_catatan(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create(['user_id' => $extrasUser->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Iklan Catatan',
            'client_ph' => 'PH Kreatif',
            'deadline' => now()->addDays(7),
            'kuota' => 5,
        ]);

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'direview_admin',
        ]);

        // 1. Admin ajukan fee awal dengan catatan
        $this->actingAs($admin)
            ->post(route('admin.negotiations.ajukan', $application), [
                'nominal' => 250000,
                'catatan' => 'Penawaran awal sesuai durasi shooting 6 jam.',
            ])
            ->assertRedirect();

        $nego1 = $application->feeNegotiations()->reorder('round', 'desc')->first();
        $this->assertNotNull($nego1);
        $this->assertSame(250000, (int) $nego1->nominal);
        $this->assertSame('Penawaran awal sesuai durasi shooting 6 jam.', $nego1->catatan);

        // 2. Extras lihat negosiasi dan ajukan counter fee dengan catatan
        $this->actingAs($extrasUser)
            ->get(route('extras.negotiations.show', $application))
            ->assertOk()
            ->assertSee('Penawaran awal sesuai durasi shooting 6 jam.');

        $counterRes = $this->actingAs($extrasUser)
            ->from(route('extras.negotiations.show', $application))
            ->post(route('extras.negotiations.counter', $application), [
                'nominal' => 300000,
                'catatan' => 'Mohon ditambah untuk transport lokasi jauh.',
            ]);

        $counterRes->assertSessionHasNoErrors();
        $counterRes->assertRedirect(route('extras.negotiations.show', $application));

        $nego2 = $application->feeNegotiations()->reorder('round', 'desc')->first();
        $this->assertSame(300000, (int) $nego2->nominal);
        $this->assertSame('Mohon ditambah untuk transport lokasi jauh.', $nego2->catatan);

        // 3. Admin lihat counter fee & catatan
        $this->actingAs($admin)
            ->get(route('admin.negotiations.show', $application))
            ->assertOk()
            ->assertSee('Mohon ditambah untuk transport lokasi jauh.');
    }

    public function test_work_history_separates_base_honor_and_reimbursements(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $admin = User::factory()->create(['role' => 'admin_default']);
        AdminProfile::create(['user_id' => $admin->id]);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Film Layar Lebar',
            'client_ph' => 'Cinema Utama',
            'deadline' => now()->addDays(10),
            'kuota' => 10,
        ]);

        $assignment = AdminProjectAssignment::create([
            'casting_project_id' => $project->id,
            'user_id' => $admin->id,
            'assigned_by' => $superAdmin->id,
            'status_log' => 'selesai',
        ]);

        $payroll = $assignment->payroll()->create([
            'nominal_pokok' => 500000,
        ]);

        $payroll->addons()->create([
            'label' => 'Bensin antar talenta ke lokasi',
            'nominal' => 75000,
            'created_by' => $superAdmin->id,
        ]);

        // Admin checks own work history
        $response = $this->actingAs($admin)->get(route('admin.work-history'));
        $response->assertOk();
        $response->assertSee('Riwayat Honor Pokok Penugasan Proyek');
        $response->assertSee('Riwayat Reimbursement &amp; Add-on Operasional', false);
        $response->assertSee('Bensin antar talenta ke lokasi');
        $response->assertSee('Rp 75.000');

        // Super Admin checks admin detail
        $saResponse = $this->actingAs($superAdmin)->get(route('super-admin.admins.show', $admin));
        $saResponse->assertOk();
        $saResponse->assertSee('Riwayat Reimbursement &amp; Biaya Tambahan', false);
        $saResponse->assertSee('Bensin antar talenta ke lokasi');
    }

    public function test_activity_log_records_across_roles_and_allows_super_admin_filtering(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $clientUser = User::factory()->create(['role' => 'client']);
        $extrasUser = User::factory()->create(['role' => 'extras']);

        // Record some logs
        ActivityLog::record(
            action: 'SUBMIT_PROJECT_REQUEST',
            description: 'Client mengajukan permintaan proyek casting baru: Iklan Kopi',
            user: $clientUser,
        );

        ActivityLog::record(
            action: 'APPLY_PROJECT',
            description: 'Extras mendaftar ke lowongan casting',
            user: $extrasUser,
        );

        // Super admin can view all logs
        $response = $this->actingAs($superAdmin)->get(route('super-admin.activity-logs'));
        $response->assertOk();
        $response->assertSee('Log Aktivitas Sistem (Audit Trail)');
        $response->assertSee('SUBMIT_PROJECT_REQUEST');
        $response->assertSee('APPLY_PROJECT');

        // Filter by role=client
        $clientFilterResponse = $this->actingAs($superAdmin)->get(route('super-admin.activity-logs', ['role' => 'client']));
        $clientFilterResponse->assertOk();
        $clientFilterResponse->assertSee('SUBMIT_PROJECT_REQUEST');
        $clientFilterResponse->assertDontSee('APPLY_PROJECT');

        // Non-super-admin gets 403
        $this->actingAs($clientUser)
            ->get(route('super-admin.activity-logs'))
            ->assertForbidden();
    }

    public function test_extras_grade_activity_log_is_visible_to_extras_in_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin_default']);
        $extrasUser = User::factory()->create(['role' => 'extras']);
        $extras = ExtrasProfile::create([
            'user_id' => $extrasUser->id,
            'grade_saat_ini' => null,
        ]);

        $project = CastingProject::create([
            'admin_id' => $admin->id,
            'nama_produksi' => 'Proyek Iklan Sampo',
            'client_ph' => 'PH Glamour',
            'deadline' => now()->addDays(5),
            'kuota' => 2,
        ]);

        $application = ProjectApplication::create([
            'casting_project_id' => $project->id,
            'extras_id' => $extras->id,
            'status_partisipasi' => 'direview_admin',
        ]);

        // Admin updates grade
        $this->actingAs($admin)->patch(route('admin.applications.grade', $application), [
            'grade' => 'A',
        ])->assertRedirect();

        $extras->refresh();
        $this->assertSame('A', $extras->grade_saat_ini);

        // Check ActivityLog recorded
        $log = ActivityLog::where('action', 'SET_EXTRAS_GRADE')
            ->where('subject_type', ExtrasProfile::class)
            ->where('subject_id', $extras->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('A', $log->properties['grade_baru']);

        // Extras visits dashboard
        $response = $this->actingAs($extrasUser)->get(route('extras.dashboard'));
        $response->assertOk();
        $response->assertSee('Grade A');
        $response->assertSee('Perubahan Grade Talenta');

        // Extras visits profile show
        $profileResponse = $this->actingAs($extrasUser)->get(route('extras.profile.show'));
        $profileResponse->assertOk();
        $profileResponse->assertSee('Grade A');
    }
}
