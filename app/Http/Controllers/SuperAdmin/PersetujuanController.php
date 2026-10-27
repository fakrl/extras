<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * CR.3.1: halaman detail "Perlu Tindakan" SA (dipindah dari dashboard, bukan
 * dibuat ulang) - isi & aksi sama persis (reuse _pengajuan), cuma tempatnya pindah.
 */
class PersetujuanController extends Controller
{
    public function index(Request $request)
    {
        $pengajuanBaru = CastingProject::where('client_request_status', 'menunggu_acc')->whereNull('alasan_tolak')
            ->with('client', 'classes', 'shootingDates', 'attachments')->latest()->get();
        $pengajuanRevisi = CastingProject::where('client_request_status', 'menunggu_acc')->whereNotNull('alasan_tolak')
            ->with('client', 'classes', 'shootingDates', 'attachments')->latest()->get();
        $adminsAktif = User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get();
        $korlapsAktif = User::where('role', User::ROLE_KORLAP)->where('status', 'aktif')->orderBy('name')->get();
        $tanpaPic = CastingProject::tanpaPicClient()->latest()->get();
        $akunPerhatianList = User::where('role', 'client')->where('wajib_ganti_password', true)
            ->where('updated_at', '<=', now()->subDays(7))->oldest('updated_at')->get();

        $tabAwal = collect([
            'pengajuan-baru' => $pengajuanBaru->count(),
            'pengajuan-revisi' => $pengajuanRevisi->count(),
            'tanpa-pic' => $tanpaPic->count(),
            'akun' => $akunPerhatianList->count(),
        ])->search(fn ($n) => $n > 0) ?: 'pengajuan-baru';

        return view('super-admin.persetujuan', compact(
            'pengajuanBaru', 'pengajuanRevisi', 'adminsAktif', 'korlapsAktif', 'tanpaPic', 'akunPerhatianList', 'tabAwal'
        ));
    }
}
