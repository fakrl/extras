<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminProjectAssignment;
use App\Models\CastingProject;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Support\AdminRingkasan;
use App\Support\KorlapRingkasan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

// SPEC BL/CV.1: pratinjau + pantau Admin/Korlap, murni baca. Aksi lewat "Masuk mode" (BD.6) atau Ingatkan.
class MonitoringController extends Controller
{
    public function admin()
    {
        $ringkasan = AdminRingkasan::untuk();
        $tahapan = AdminRingkasan::tahapan();

        $adminsAktif = User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get();

        $pantauAdmin = AdminRingkasan::pantauAdmin($adminsAktif, null);
        $kelompokPerAdmin = AdminRingkasan::kelompok($pantauAdmin, $adminsAktif, null)
            ->groupBy(fn ($g) => $g['admin']->id)
            ->map(fn ($grup) => $grup->groupBy('proyek_id'));

        $proyekDipegang = $this->proyekDipegangPerStaf($adminsAktif);

        $admins = $adminsAktif->load('aktivitasTerakhir')->map(function (User $u) use ($pantauAdmin, $kelompokPerAdmin, $proyekDipegang) {
            // BL: dipakai test lama (sama dengan dashboard Admin), tetap dihitung meski tak dirender di sini.
            $r = AdminRingkasan::untuk($u);
            $u->menunggu = $r['nego']['jumlah'] + $r['kontrak']['jumlah'];
            $p = $pantauAdmin->first(fn ($p) => $p['admin']->id === $u->id);
            $u->jumlah_tugas = $p['jumlah'] ?? 0;
            $u->tugas_tertua = $p ? $p['tugas']->sortBy(fn ($t) => $t['sejak']?->timestamp ?? 0)->first()['sejak'] ?? null : null;
            $u->tugas_tua = $u->tugas_tertua && $u->tugas_tertua->lte(now()->subDays(3));
            $u->proyek_dipegang = (int) ($proyekDipegang[$u->id] ?? 0);
            $u->kelompok_proyek = $kelompokPerAdmin->get($u->id, collect());

            return $u;
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

        $korlapsAktif = User::where('role', User::ROLE_KORLAP)->where('status', 'aktif')->orderBy('name')->get();
        $tugasKorlap = KorlapRingkasan::tugasTertunda($korlapsAktif, null);
        $proyekDipegang = $this->proyekDipegangPerStaf($korlapsAktif);

        $korlaps = $korlapsAktif->load('aktivitasTerakhir')->map(function (User $k) use ($tugasKorlap, $proyekDipegang) {
            $t = $tugasKorlap->first(fn ($t) => $t['korlap']->id === $k->id);
            $k->jumlah_tugas = $t['jumlah'] ?? 0;
            $k->tugas_tertua = $t ? $t['tugas']->sortBy(fn ($g) => $g['sejak']?->timestamp ?? 0)->first()['sejak'] ?? null : null;
            $k->tugas_tua = $k->tugas_tertua && $k->tugas_tertua->lte(now()->subDays(3));
            $k->proyek_dipegang = (int) ($proyekDipegang[$k->id] ?? 0);
            $k->kelompok_proyek = $t ? $t['tugas']->groupBy('proyek_id') : collect();

            return $k;
        });

        return view('super-admin.monitoring.korlap', compact('hari', 'terdekat', 'menunggu', 'catatan', 'korlaps'));
    }

    /**
     * CV.1.2: "jumlah proyek dipegang" per staf = sama dengan definisi CastingProject::dikelolaOleh()
     * (admin_id ATAU adminAssignments), bukan cuma adminAssignments - PIC lewat
     * Admin\CastingProjectController::store() tidak selalu tercatat juga di adminAssignments.
     */
    private function proyekDipegangPerStaf(Collection $staf): Collection
    {
        $ids = $staf->pluck('id');
        $dariPic = CastingProject::whereIn('admin_id', $ids)->get(['id', 'admin_id'])->map(fn ($p) => ['id' => $p->id, 'user_id' => $p->admin_id]);
        $dariAssign = AdminProjectAssignment::whereIn('user_id', $ids)->get(['casting_project_id', 'user_id'])->map(fn ($a) => ['id' => $a->casting_project_id, 'user_id' => $a->user_id]);

        return $dariPic->concat($dariAssign)->groupBy('user_id')->map(fn ($g) => $g->pluck('id')->unique()->count());
    }

    /** CV.1.4: Ingatkan Korlap per kelompok (proyek+jenis), pola sama dengan AdminRingkasan::kelompok(). */
    public function ingatkanKorlap(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'korlap_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_KORLAP)],
            'proyek_id' => ['required', 'integer', 'exists:casting_projects,id'],
            'jenis' => ['required', 'in:selfie_menunggu,absen_belum'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $korlap = User::findOrFail($data['korlap_id']);
        $grup = KorlapRingkasan::tugasTertunda(collect([$korlap]), null)->first();
        $item = $grup ? $grup['tugas']->first(fn ($g) => $g['proyek_id'] === (int) $data['proyek_id'] && $g['jenis'] === $data['jenis']) : null;
        abort_unless($item, 422);

        $kunci = $item['kunci'];
        $terakhir = $korlap->notifications()->where('data->jenis', 'pengingat_sa')->where('data->kunci', $kunci)->latest()->first();
        if ($terakhir && $terakhir->created_at->gt(now()->subDay())) {
            return back()->with('error', 'Korlap ini sudah diingatkan untuk tugas ini dalam 24 jam terakhir.');
        }

        $catatan = $data['catatan'] ?? null;
        $pesan = "Super Admin mengingatkan: {$item['tugas']} · {$item['jumlah']} Extras di {$item['proyek']}".($catatan ? ". Catatan: {$catatan}" : '');
        $korlap->notify(new InAppNotification('Pengingat dari Super Admin', $pesan, $item['url'], ['jenis' => 'pengingat_sa', 'kunci' => $kunci]));

        ActivityLog::record('REMIND_KORLAP', "Super Admin mengingatkan {$korlap->name}: {$item['tugas']} ({$item['jumlah']} Extras) di {$item['proyek']}", CastingProject::find($data['proyek_id']));

        return back()->with('status', "Pengingat terkirim ke {$korlap->name}.");
    }
}
