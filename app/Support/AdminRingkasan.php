<?php

namespace App\Support;

use App\Models\CastingProject;
use App\Models\Contract;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

// BL.1: satu sumber angka "perlu tindakan" Admin, dipakai dashboard Admin & pratinjau Monitoring SA.
// CM.1.2: scoping proyek lewat CastingProject::dikelolaOleh(), bukan admin_id langsung.
class AdminRingkasan
{
    public static function untuk(?User $admin = null): array
    {
        $proyek = fn ($q) => $q->when($admin, fn ($q) => $q->whereHas('castingProject', fn ($p) => $p->dikelolaOleh($admin)));
        $lamaran = fn ($q) => $q->when($admin, fn ($q) => $q->whereHas('projectApplication.castingProject', fn ($p) => $p->dikelolaOleh($admin)));
        $url = fn (array $q) => route('admin.projects.index', $q, false);

        return [
            'nego' => [
                'label' => 'Nego menunggu balasan Admin',
                'jumlah' => ProjectApplication::where('status_partisipasi', 'nego_fee')
                    ->whereHas('feeNegotiations', fn ($n) => $n->where('diajukan_oleh', 'extras')
                        ->whereRaw('round = (select max(f2.round) from fee_negotiations f2 where f2.project_application_id = fee_negotiations.project_application_id)'))
                    ->tap($proyek)->count(),
                'url' => $url(['peserta' => 'nego_fee']),
            ],
            'deal' => [
                'label' => 'Kandidat Deal siap diajukan ke Client',
                'jumlah' => ProjectApplication::where('status_partisipasi', 'deal')->tap($proyek)->count(),
                'url' => $url(['peserta' => 'deal']),
            ],
            'kontrak' => [
                'label' => 'Kontrak menunggu TTD Admin',
                'jumlah' => Contract::whereNull('ttd_admin_signature_path')->whereNull('voided_at')->tap($lamaran)->count(),
                'url' => $url(['peserta' => 'lolos']),
            ],
            'bayar' => [
                'label' => 'Pembayaran Extras belum ditransfer',
                'jumlah' => Payment::perluDitransfer()->tap($lamaran)->count(),
                'url' => $url(['bayar' => 'extras']),
            ],
            'tanpa_pic' => [
                'label' => 'Proyek tanpa PIC/Client',
                'jumlah' => CastingProject::tanpaPicClient()
                    ->when($admin, fn ($q) => $q->dikelolaOleh($admin))->count(),
                'url' => $url(['tanpa_client' => 1]),
            ],
            // CN.3.1: proyek sudah disetujui tapi ada peran yang budget per orangnya belum diatur Admin PIC.
            'atur_budget' => [
                'label' => 'Atur budget per orang tiap peran',
                'jumlah' => CastingProject::where('client_request_status', 'disetujui')
                    ->whereHas('classes', fn ($q) => $q->whereNull('budget_client'))
                    ->when($admin, fn ($q) => $q->dikelolaOleh($admin))->count(),
                'url' => $url([]),
            ],
        ];
    }

    const TAHAP = [
        'ajuan' => ['label' => 'Ajuan', 'status' => ['diajukan', 'direview_admin'], 'filter' => ['peserta' => 'diajukan']],
        'nego' => ['label' => 'Nego Fee', 'status' => ['nego_fee', 'deal'], 'filter' => ['peserta' => 'nego_fee']],
        'client' => ['label' => 'Dipilih Client', 'status' => ['diajukan_ke_client'], 'filter' => ['peserta' => 'diajukan_ke_client']],
        'kontrak' => ['label' => 'Kontrak', 'status' => ['lolos'], 'filter' => ['peserta' => 'lolos']],
        'selesai' => ['label' => 'Pembayaran', 'status' => ['kontrak_ditandatangani'], 'filter' => ['bayar' => 'extras']],
    ];

    /** BP: step-bar tahapan kandidat + daftar siapa & aksi berikutnya, urut paling lama menunggu. */
    public static function tahapan(?User $admin = null, int $maks = 8): array
    {
        $apps = self::lamaranAktif()
            ->when($admin, fn ($q) => $q->whereHas('castingProject', fn ($p) => $p->dikelolaOleh($admin)))
            ->get()->map(fn ($a) => self::langkah($a))->filter();

        return collect(self::TAHAP)->map(function ($t, $key) use ($apps, $maks) {
            $isi = $apps->whereIn('app.status_partisipasi', $t['status']);

            return [
                'label' => $t['label'],
                'jumlah' => $isi->count(),
                'perlu' => $isi->where('perlu', true)->count(),
                'url' => route('admin.projects.index', $t['filter'], false),
                'daftar' => $isi->sortBy(fn ($i) => $i['sejak']?->timestamp ?? 0)->take($maks)->values(),
            ];
        })->all();
    }

    /**
     * CO.2.1/CR.3.2: per Admin aktif, tugas tertunda di proyek yang dia PIC-i.
     * $maks null = semua (halaman Pantau Admin), int = batasi TAMPILAN per Admin (kartu dashboard ringkas).
     * 'jumlah' = total tugas perlu SEBELUM dipotong $maks, dipakai KPI dashboard SA.
     */
    public static function pantauAdmin(Collection $admins, ?int $maks = 5): Collection
    {
        // tahapan($a) HARUS dipanggil dengan maks PHP_INT_MAX supaya daftar per tahap
        // tidak terpotong 8 item tertua sebelum difilter perlu=true ($maks di sini cuma batasi tampilan).
        $perAdmin = $admins->map(function (User $a) use ($maks) {
            $semua = collect(self::tahapan($a, PHP_INT_MAX))
                ->flatMap(fn ($t) => $t['daftar'])
                ->filter(fn ($i) => $i['perlu'] && $i['app']->castingProject?->admin_id === $a->id)
                ->sortBy(fn ($i) => $i['sejak']?->timestamp ?? 0)
                ->values();

            return ['admin' => $a, 'jumlah' => $semua->count(), 'tampil' => $semua->when($maks, fn ($c) => $c->take($maks))->values()];
        })->filter(fn ($p) => $p['jumlah'] > 0)->values();

        // N+1: satu query notifikasi untuk semua tugas yang ditampilkan, bukan per tugas.
        $kunciSemua = $perAdmin->flatMap(fn ($p) => $p['tampil'])->map(fn ($i) => 'pa-'.$i['app']->id)->values();
        $notifTerakhir = DatabaseNotification::where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $admins->pluck('id'))
            ->where('data->jenis', 'pengingat_sa')
            ->whereIn('data->kunci', $kunciSemua)
            ->get()
            ->groupBy(fn ($n) => $n->data['kunci'] ?? null)
            ->map(fn ($g) => $g->sortByDesc('created_at')->first());

        return $perAdmin->map(function ($p) use ($notifTerakhir) {
            $tugas = $p['tampil']->map(function ($i) use ($notifTerakhir) {
                $terakhir = $notifTerakhir->get('pa-'.$i['app']->id);

                return [
                    'app_id' => $i['app']->id,
                    'proyek' => $i['app']->castingProject?->nama_produksi,
                    'proyek_id' => $i['app']->casting_project_id,
                    'tugas' => $i['teks'],
                    'jenis' => $i['jenis'],
                    'sejak' => $i['sejak'],
                    'url' => $i['url'],
                    'diingatkan_pada' => $terakhir && $terakhir->created_at->gt(now()->subDay()) ? $terakhir->created_at : null,
                ];
            })->values();

            return ['admin' => $p['admin'], 'jumlah' => $p['jumlah'], 'tugas' => $tugas];
        });
    }

    /**
     * CU.7/CV.1: kelompokkan hasil pantauAdmin() per Admin+proyek+jenis tugas (bukan per
     * kandidat), status "sudah diingatkan" 24 jam per kelompok (kunci 'pg-{admin}-{proyek}-{jenis}').
     * Dipakai dashboard SA (kartu, $take=5) & Monitoring Admin (akordeon per proyek, $take=null).
     * Logika sama persis dengan DashboardController::diingatkanKelompok() sebelum CV - jangan diubah perilakunya.
     */
    public static function kelompok(Collection $pantauAdmin, Collection $adminsAktif, ?int $take = null): Collection
    {
        $mentah = $pantauAdmin
            ->flatMap(fn ($p) => $p['tugas']->map(fn ($t) => $t + ['admin' => $p['admin']]))
            ->groupBy(fn ($t) => $t['admin']->id.'|'.$t['proyek_id'].'|'.$t['jenis']);

        $kunciSemua = $mentah->keys()->map(function ($k) {
            [$adminId, $proyekId, $jenis] = explode('|', $k, 3);

            return "pg-{$adminId}-{$proyekId}-{$jenis}";
        })->values();

        $notifTerakhir = DatabaseNotification::where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $adminsAktif->pluck('id'))
            ->where('data->jenis', 'pengingat_sa')
            ->whereIn('data->kunci', $kunciSemua)
            ->get()
            ->groupBy(fn ($n) => $n->data['kunci'] ?? null)
            ->map(fn ($g) => $g->sortByDesc('created_at')->first());

        $hasil = $mentah->map(function ($grup, $kunciGrup) use ($notifTerakhir) {
            [$adminId, $proyekId, $jenis] = explode('|', $kunciGrup, 3);
            $pertama = $grup->sortBy(fn ($t) => $t['sejak']?->timestamp ?? 0)->first();
            $kunci = "pg-{$adminId}-{$proyekId}-{$jenis}";
            $terakhir = $notifTerakhir->get($kunci);
            // Kompatibel dengan pengingat per-kandidat lama ('pa-...', dari halaman Pantau
            // Admin): kalau SEMUA kandidat di kelompok ini sudah diingatkan satu-satu dalam
            // 24 jam, kelompok juga dianggap "sudah diingatkan" (meski belum ada notif 'pg-').
            $semuaPaDiingatkan = $grup->every(fn ($t) => $t['diingatkan_pada'] !== null);
            $diingatkanPada = $terakhir && $terakhir->created_at->gt(now()->subDay())
                ? $terakhir->created_at
                : ($semuaPaDiingatkan ? $grup->sortByDesc(fn ($t) => $t['diingatkan_pada']->timestamp)->first()['diingatkan_pada'] : null);

            return [
                'admin' => $pertama['admin'],
                'proyek' => $pertama['proyek'],
                'proyek_id' => (int) $proyekId,
                'jenis' => $jenis,
                'tugas' => $jenis === 'balas_nego' ? 'Counter nego menunggu balasan' : $pertama['tugas'],
                'jumlah' => $grup->count(),
                'sejak' => $pertama['sejak'],
                'app_ids' => $grup->pluck('app_id')->values(),
                'diingatkan_pada' => $diingatkanPada,
                'kunci' => $kunci,
            ];
        })->sortBy(fn ($g) => $g['sejak']?->timestamp ?? 0)->values();

        return $take ? $hasil->take($take)->values() : $hasil;
    }

    private static function lamaranAktif()
    {
        return ProjectApplication::whereIn('status_partisipasi', array_merge(...array_column(self::TAHAP, 'status')))
            ->with(['extras.user', 'castingProject', 'castingProjectClass', 'contract', 'payment', 'feeNegotiations']);
    }

    /** Jumlah lamaran "perlu tindakan" per proyek (id => n), definisi sama dengan step-bar dashboard. */
    public static function perluPerProyek(array $projectIds): array
    {
        return self::lamaranAktif()->whereIn('casting_project_id', $projectIds)->get()
            ->map(fn ($a) => self::langkah($a))->filter(fn ($i) => $i && $i['perlu'])
            ->countBy(fn ($i) => $i['app']->casting_project_id)->all();
    }

    private static function langkah(ProjectApplication $a): ?array
    {
        $lineup = route('admin.projects.applicants', [$a->casting_project_id, 'q' => $a->extras?->user?->username], false).'#app-'.$a->id;
        $nego = $a->feeNegotiations->last();
        $kontrak = $a->contract;
        $bayar = $a->payment;
        $rp = fn ($n) => 'Rp '.number_format((float) $n, 0, ',', '.');

        [$teks, $perlu, $sejak, $tombol, $url, $jenis] = match ($a->status_partisipasi) {
            'diajukan' => ['Belum direview — beri grade / tolak', true, $a->updated_at, 'Review', $lineup, 'review_awal'],
            'direview_admin' => ['Sudah di-grade — ajukan fee awal', true, $a->updated_at, 'Ajukan fee', $lineup, 'ajukan_fee'],
            'nego_fee' => $nego?->diajukan_oleh === 'extras'
                ? ['Extras counter '.$rp($nego->nominal).' — balas', true, $nego->created_at, 'Balas nego', route('admin.negotiations.show', $a, false), 'balas_nego']
                : ['Menunggu balasan Extras', false, $nego?->created_at ?? $a->updated_at, 'Lihat nego', route('admin.negotiations.show', $a, false), 'nego_menunggu'],
            'deal' => ['Siap diajukan ke Client', true, $a->updated_at, 'Ajukan', $lineup, 'ajukan_client'],
            'diundang' => ['Menunggu persetujuan Extras', false, $a->diundang_at ?? $a->updated_at, 'Lihat', $lineup, 'undangan_menunggu'],
            'diajukan_ke_client' => ['Menunggu keputusan Client', false, $a->updated_at, 'Lihat', $lineup, 'client_menunggu'],
            'lolos' => match (true) {
                ! $kontrak => ['Menunggu Extras lengkapi nama asli & NIK', false, $a->updated_at, 'Lihat', $lineup, 'lengkapi_ktp'],
                ! $kontrak->ttd_admin_signature_path && ! $kontrak->voided_at => ['TTD Admin belum', true, $kontrak->created_at, 'Tanda tangan', route('contracts.show', $a, false), 'ttd_admin'],
                default => ['Tunggu TTD Extras', false, $kontrak->updated_at, 'Lihat kontrak', route('contracts.show', $a, false), 'ttd_extras_menunggu'],
            },
            'kontrak_ditandatangani' => match ($bayar?->status) {
                'dikonfirmasi_diterima' => [null, false, null, null, null, null],
                'ditransfer' => ['Menunggu konfirmasi Extras', false, $bayar->ditransfer_at ?? $bayar->updated_at, 'Lihat', route('payments.show', $a, false), 'konfirmasi_menunggu'],
                'disengketakan' => ['Extras menyengketakan pembayaran — tinjau', true, $bayar->updated_at, 'Tinjau', route('payments.show', $a, false), 'sengketa_bayar'],
                'belum_dibayar' => ['Honor belum ditransfer', true, $kontrak?->signed_at ?? $bayar->created_at, 'Transfer', route('payments.show', $a, false), 'honor_belum_transfer'],
                default => ['Data pembayaran belum dibuat', false, $a->updated_at, 'Lihat', route('payments.show', $a, false), 'bayar_belum_ada'],
            },
        };

        return $teks ? ['app' => $a, 'teks' => $teks, 'perlu' => $perlu, 'sejak' => $sejak, 'tombol' => $tombol, 'url' => $url, 'lineup' => $lineup, 'jenis' => $jenis] : null;
    }
}
