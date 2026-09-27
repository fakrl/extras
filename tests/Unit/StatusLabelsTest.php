<?php

namespace Tests\Unit;

use App\Models\CastingProject;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class StatusLabelsTest extends TestCase
{
    public function test_project_application_semua_status_partisipasi_punya_label_dan_badge(): void
    {
        $statuses = [
            'diajukan', 'direview_admin', 'nego_fee', 'deal', 'diajukan_ke_cd',
            'direview_cd', 'lolos', 'ditolak', 'kontrak_ditandatangani',
            'selesai_produksi', 'dibatalkan',
        ];

        foreach ($statuses as $status) {
            $app = new ProjectApplication(['status_partisipasi' => $status]);
            $this->assertNotEmpty($app->label());
            $this->assertStringStartsWith('badge-', $app->badgeClass());
        }
    }

    public function test_payment_semua_status_punya_label_dan_badge(): void
    {
        $statuses = ['belum_dibayar', 'ditransfer', 'dikonfirmasi_diterima', 'disengketakan'];

        foreach ($statuses as $status) {
            $payment = new Payment(['status' => $status]);
            $this->assertNotEmpty($payment->label());
            $this->assertStringStartsWith('badge-', $payment->badgeClass());
        }
    }

    public function test_user_semua_role_resmi_pakai_badge_netral(): void
    {
        foreach (User::ROLES as $role) {
            $user = new User(['role' => $role]);
            $this->assertNotEmpty($user->label());
            $this->assertSame('badge-netral', $user->badgeClass());
        }
    }

    public function test_casting_project_semua_status_punya_label_dan_badge(): void
    {
        foreach (['dibuka', 'ditutup'] as $status) {
            $project = new CastingProject(['status' => $status]);
            $this->assertNotEmpty($project->label());
            $this->assertStringStartsWith('badge-', $project->badgeClass());
        }
    }

    public function test_nilai_tak_dikenal_fallback_ke_ucfirst(): void
    {
        $app = new ProjectApplication(['status_partisipasi' => 'status_baru']);
        $this->assertSame('Status baru', $app->label());
        $this->assertSame('badge-netral', $app->badgeClass());
    }
}
