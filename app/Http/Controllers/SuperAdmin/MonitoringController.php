<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Support\AdminRingkasan;
use App\Support\KorlapRingkasan;

// SPEC BL: pratinjau Monitoring Admin/Korlap, murni baca. Aksi lewat "Masuk mode" (BD.6).
class MonitoringController extends Controller
{
    public function admin()
    {
        $ringkasan = AdminRingkasan::untuk();
        $tahapan = AdminRingkasan::tahapan();

        $admins = User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')
            ->withCount(['castingProjects as proyek_aktif' => fn ($q) => $q->where('status', 'dibuka')])
            ->with('aktivitasTerakhir')->orderBy('name')->get()
            ->each(function (User $u) {
                $r = AdminRingkasan::untuk($u->id);
                $u->menunggu = $r['nego']['jumlah'] + $r['kontrak']['jumlah'];
            });

        $proyek = CastingProject::where(fn ($q) => $q->diTahap('berjalan')->orWhere(fn ($q) => $q->diTahap('mendatang')))
            ->with(['admin:id,name', 'shootingDates'])
            ->withCount([
                'applications',
                'applications as terisi' => fn ($q) => $q->whereNotIn('status_partisipasi', ['ditolak', 'dibatalkan']),
                'applications as deal' => fn ($q) => $q->where('status_partisipasi', 'deal'),
                'applications as lolos' => fn ($q) => $q->whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS),
            ])
            ->withMin(['shootingDates as tanggal_acuan' => fn ($d) => $d->whereDate('tanggal', '>=', today())], 'tanggal')
            ->orderByRaw('tanggal_acuan is null')->orderBy('tanggal_acuan')
            ->take(5)->get();

        return view('super-admin.monitoring.admin', compact('ringkasan', 'tahapan', 'admins', 'proyek'));
    }

    public function korlap()
    {
        $hari = [
            'Hari ini' => KorlapRingkasan::shooting(today()->toDateString()),
            'Besok' => KorlapRingkasan::shooting(today()->addDay()->toDateString()),
        ];
        $terdekat = $hari['Hari ini']->isEmpty() ? KorlapRingkasan::shootingTerdekat() : null;
        $menunggu = KorlapRingkasan::menungguValidasi();
        $catatan = KorlapRingkasan::catatanTerbaru();

        return view('super-admin.monitoring.korlap', compact('hari', 'terdekat', 'menunggu', 'catatan'));
    }
}
