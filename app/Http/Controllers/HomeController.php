<?php

namespace App\Http\Controllers;

use App\Models\CastingProject;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    const CAST_TAMPIL = 6;

    const PORTO_MAKS = 8;

    public function index(Request $request)
    {
        $cast = $this->dataCast($request);

        if ($request->ajax() || $request->query('fragmen') === 'cast') {
            return response()->view('partials.cast-fragment', $cast)
                ->header('Cache-Control', 'no-store')
                ->header('Vary', 'X-Requested-With');
        }

        $allTerbuka = CastingProject::where('status', 'dibuka')->where('client_request_status', 'disetujui')
            ->select(['id', 'nama_produksi', 'client_id', 'deadline', 'kuota', 'is_urgent', 'status', 'client_request_status', 'poster_path', 'tampilkan_nama_client'])
            ->orderBy('deadline')
            ->with(['client:id,name,nama_perusahaan', 'classes:id,casting_project_id,nama_kelas,kuota_kelas', 'shootingDates:id,casting_project_id,tanggal'])
            ->get()
            ->filter(fn ($p) => $p->menerimaPendaftaran());

        $proyekTerbuka = $allTerbuka->take(6)->values();
        $adaLebih = $allTerbuka->count() > 6;

        $totalCastingDibuka = $allTerbuka->count();
        $totalPeranDibuka = $allTerbuka->sum(fn ($p) => $p->totalKuota());
        $totalTalentTerdaftar = ExtrasProfile::count();
        $rentangDeadline = $allTerbuka->isEmpty() ? null : $this->rentangDeadline($allTerbuka->min('deadline'), $allTerbuka->max('deadline'));

        $portofolio = CastingProject::where('tampil_portofolio', true)
            ->select(['id', 'nama_produksi', 'client_id', 'poster_path', 'portofolio_judul', 'portofolio_jenis', 'portofolio_tahun', 'tampilkan_nama_client'])
            ->with('client:id,name,nama_perusahaan')
            ->orderByDesc('portofolio_tahun')
            ->latest('updated_at')
            ->take(self::PORTO_MAKS)
            ->get();

        return view('welcome', array_merge(compact(
            'proyekTerbuka', 'adaLebih', 'portofolio',
            'totalCastingDibuka', 'totalPeranDibuka', 'totalTalentTerdaftar', 'rentangDeadline'
        ), $cast));
    }

    /**
     * BH.2/BH.4: 6 talent acak + filter kategori publik (Tembok Visibilitas: kategori privat/ngasal
     * diperlakukan persis sama seperti tanpa filter, tanpa bocor info lewat beda perilaku).
     * Dipakai render halaman penuh maupun respons fragmen AJAX (?fragmen=cast), satu sumber markup.
     */
    private function dataCast(Request $request): array
    {
        $grupPublik = array_values(array_diff(array_keys(ExtrasCategory::GRUP), ExtrasCategory::GRUP_PRIVAT));
        $totalPool = ExtrasProfile::tampilDiBeranda()->count();

        if ($totalPool < 4) {
            return ['castTampil' => false, 'castExtras' => collect(), 'castChips' => collect(), 'castKategoriAktif' => null, 'castAdaLebih' => false];
        }

        $kategori = $request->query('kategori');
        $kategoriAktif = is_string($kategori) && ExtrasCategory::where('nama', $kategori)->whereIn('grup', $grupPublik)->exists()
            ? $kategori : null;

        $query = ExtrasProfile::tampilDiBeranda()
            ->with(['user:id,username', 'categories' => fn ($q) => $q->whereIn('grup', $grupPublik)]);
        if ($kategoriAktif) {
            $query->whereHas('categories', fn ($q) => $q->where('nama', $kategoriAktif)->whereIn('grup', $grupPublik));
        }
        $castExtras = $query->inRandomOrder()->limit(self::CAST_TAMPIL)->get(['id', 'user_id', 'share_token']);

        $urutanGrup = array_keys(ExtrasCategory::GRUP);
        $castChips = ExtrasCategory::whereIn('grup', $grupPublik)
            ->withCount(['extrasProfiles as jumlah' => fn ($q) => $q->tampilDiBeranda()])
            ->get(['id', 'nama', 'grup'])
            ->filter(fn ($c) => $c->jumlah > 0)
            ->sort(fn ($a, $b) => $b->jumlah <=> $a->jumlah ?: array_search($a->grup, $urutanGrup) <=> array_search($b->grup, $urutanGrup))
            ->values()
            ->take(8);

        return [
            'castTampil' => true,
            'castExtras' => $castExtras,
            'castChips' => $castChips,
            'castKategoriAktif' => $kategoriAktif,
            'castAdaLebih' => $totalPool > self::CAST_TAMPIL,
        ];
    }

    private function rentangDeadline($min, $max): string
    {
        return $min->isSameDay($max)
            ? $min->translatedFormat('d M Y')
            : $min->translatedFormat('d M').' – '.$max->translatedFormat('d M Y');
    }
}
