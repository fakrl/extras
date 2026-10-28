<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\Invoice;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Services\KeuanganService;
use App\Support\AdminRingkasan;
use App\Support\Periode;
use App\Support\Timeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * CR.2: dashboard SA diringkas ke yang perlu diputuskan/ditindak saja.
     * Detail lengkap (daftar Proyek, Persetujuan, Pantau Admin) ada di
     * halamannya masing-masing - lihat Persetujuan/PantauAdminController.
     */
    public function index(Request $request)
    {
        // CR.1.1: rentang angka/statistik (KPI "Proyek berjalan").
        [$dari, $sampai, $preset] = Periode::dariSesi($request, 'periode_sa', '30');
        $jumlahHari = (int) $dari->copy()->startOfDay()->diffInDays($sampai->copy()->startOfDay()) + 1;
        // CR.1.2: navigasi timeline kartu Jadwal, terpisah dari rentang angka di atas.
        [$dariJadwal, $sampaiJadwal, $skala] = Timeline::dariSesi($request, 'timeline_sa', 'minggu');

        $pengajuanBaru = CastingProject::where('client_request_status', 'menunggu_acc')->whereNull('alasan_tolak')
            ->with('client', 'classes', 'shootingDates', 'attachments')->latest()->get();
        $pengajuanRevisi = CastingProject::where('client_request_status', 'menunggu_acc')->whereNotNull('alasan_tolak')
            ->with('client', 'classes', 'shootingDates', 'attachments')->latest()->get();
        $adminsAktif = User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get();
        $korlapsAktif = User::where('role', User::ROLE_KORLAP)->where('status', 'aktif')->orderBy('name')->get();
        $tanpaPic = CastingProject::tanpaPicClient()->latest()->get();
        // Dasar "> 7 hari": updated_at (bukan created_at) supaya akun yang direset
        // passwordnya belakangan (AdminManagementController::resetPassword) dihitung
        // sejak reset, bukan sejak akun dibuat.
        $akunPerhatianList = User::where('role', 'client')->where('wajib_ganti_password', true)
            ->where('updated_at', '<=', now()->subDays(7))->oldest('updated_at')->get();
        $akunPerhatian = $akunPerhatianList->count();

        // CR.2.2: "Menunggu keputusan" = 3 jenis keputusan proyek (akun perlu
        // perhatian tetap tampil di daftar "Butuh keputusan Anda" & halaman
        // Persetujuan, tapi tidak masuk angka KPI ini - beda sifat, bukan "keputusan").
        $menungguKeputusan = $pengajuanBaru->count() + $pengajuanRevisi->count() + $tanpaPic->count();
        $jumlahTindakan = $menungguKeputusan + $akunPerhatian;
        $pengajuanMendesak = $pengajuanBaru->merge($pengajuanRevisi)->contains(fn ($p) => $p->created_at?->lte(now()->subDays(2)));

        // CU.7 fix: maks null (bukan default 5) - $pantauAdmin dipakai diingatkanKelompok() untuk
        // menghitung & mengelompokkan SEMUA kandidat, bukan cuma 5 tertua per Admin. Dashboard SA
        // sendiri tidak menampilkan daftar 'tugas' per Admin satu-satu, jadi aman tanpa batas di sini.
        $pantauAdmin = AdminRingkasan::pantauAdmin($adminsAktif, null);
        // Pakai total lengkap ('jumlah', sebelum dipotong tampilan), bukan count() tugas yang sudah take(5).
        $pantauTotal = $pantauAdmin->sum('jumlah');
        $pantauMendesak = $pantauAdmin->contains(fn ($p) => $p['tugas']->contains(fn ($t) => $t['sejak']?->lte(now()->subDays(3))));

        // CR.2.3: gabungan 3 jenis "perlu keputusan", urut paling lama menunggu, maks 5.
        $butuhKeputusan = $pengajuanBaru->concat($pengajuanRevisi)
            ->map(fn (CastingProject $p) => ['tipe' => 'pengajuan', 'req' => $p, 'sejak' => $p->created_at])
            ->concat($tanpaPic->map(fn (CastingProject $p) => ['tipe' => 'tanpa_pic', 'project' => $p, 'sejak' => $p->created_at]))
            ->concat($akunPerhatianList->map(fn (User $u) => ['tipe' => 'akun', 'user' => $u, 'sejak' => $u->updated_at]))
            ->sortBy('sejak')
            ->take(5)
            ->values();

        // CU.7: "Admin perlu diingatkan" dikelompokkan per Admin+proyek+jenis tugas
        // (bukan per kandidat) supaya tidak tampak baris kembar - hitungan "N Extras"
        // + satu tombol Ingatkan per kelompok. Halaman Pantau Admin TETAP rinci per
        // kandidat (pakai $pantauAdmin apa adanya, tidak disentuh).
        $diingatkan = $this->diingatkanKelompok($pantauAdmin, $adminsAktif);

        // CR.2.2 KPI "Proyek berjalan": shooting dalam rentang angka + pertumbuhan vs periode sebelumnya, panjang sama.
        $proyekBerjalan = CastingProject::diTahap('berjalan')->shootingDalam($dari, $sampai)->count();
        $sampaiSebelum = $dari->copy()->subDay();
        $dariSebelum = $sampaiSebelum->copy()->subDays($jumlahHari - 1);
        $proyekBerjalanSebelum = CastingProject::diTahap('berjalan')->shootingDalam($dariSebelum, $sampaiSebelum)->count();
        $pertumbuhanBerjalan = $proyekBerjalanSebelum > 0
            ? (int) round((($proyekBerjalan - $proyekBerjalanSebelum) / $proyekBerjalanSebelum) * 100)
            : ($proyekBerjalan > 0 ? 100 : null);

        $invoiceBelumLunas = Invoice::where('status_bayar', '!=', 'lunas')
            ->with('castingProject.classes')->get()
            ->each(fn (Invoice $i) => $i->nilai = (float) ($i->nominal ?? $this->keuanganService->nilaiInvoice($i->castingProject)));
        $invoiceTotal = $invoiceBelumLunas->sum('nilai');

        // CR.2.5: jadwal maks 5 TANGGAL berkegiatan (bukan 5 event) - agenda sendiri tidak diubah (CQ.2).
        $agendaEvents = $this->jadwalEvents($dariJadwal, $sampaiJadwal)
            ->groupBy(fn (EventShootingDate $e) => Carbon::parse($e->tanggal)->format('Y-m-d'))
            ->take(5)
            ->flatten(1);

        return view('super-admin.dashboard', compact(
            'dari', 'sampai', 'preset', 'jumlahHari',
            'dariJadwal', 'sampaiJadwal', 'skala',
            'adminsAktif', 'korlapsAktif',
            'menungguKeputusan', 'jumlahTindakan', 'pengajuanMendesak',
            'pantauAdmin', 'pantauTotal', 'pantauMendesak',
            'butuhKeputusan', 'diingatkan',
            'proyekBerjalan', 'pertumbuhanBerjalan',
            'invoiceBelumLunas', 'invoiceTotal',
            'agendaEvents'
        ));
    }

    private function jadwalEvents($dari, $sampai)
    {
        return EventShootingDate::whereDate('tanggal', '>=', $dari)
            ->whereDate('tanggal', '<=', $sampai)
            ->whereHas('castingProject', fn ($q) => $q->where('client_request_status', 'disetujui'))
            ->with(['castingProject' => fn ($q) => $q->select('id', 'nama_produksi')->withCount(['applications as extras_count' => fn ($a) => $a
                ->whereIn('status_partisipasi', [...ProjectApplication::STATUS_AKTIF, 'selesai_produksi'])])])
            ->withCount(['attendances as hadir_count' => fn ($q) => $q->where('status', 'hadir')])
            ->orderBy('tanggal')
            ->get()
            ->each(function (EventShootingDate $e) {
                $e->nama_produksi = $e->castingProject?->nama_produksi;
                $e->jumlah_extras = (int) $e->castingProject?->extras_count;
                $e->absensi = "{$e->hadir_count}/{$e->jumlah_extras} hadir";
                $e->url_proyek = route('admin.projects.show', $e->casting_project_id);
                $e->url_absensi = route('admin.attendance.index', ['project' => $e->casting_project_id, 'tanggal' => $e->id]);
            });
    }

    /**
     * CO.2.2/CO.2.3: ingatkan Admin PIC, notif in-app saja, maks 1x/24 jam per tugas.
     * CU.7: `project_application_ids` (jamak) = pengingat satu kelompok dari kartu
     * dashboard (Admin+proyek+jenis tugas sama); `project_application_id` (tunggal,
     * dari halaman Pantau Admin) perilakunya TIDAK diubah sama sekali.
     */
    public function ingatkanAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_application_id' => ['required_without:project_application_ids', 'integer', Rule::exists('project_applications', 'id')],
            'project_application_ids' => ['required_without:project_application_id', 'array', 'min:1'],
            'project_application_ids.*' => ['integer', Rule::exists('project_applications', 'id')],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        return isset($data['project_application_ids'])
            ? $this->ingatkanKelompok($data['project_application_ids'], $data['catatan'] ?? null)
            : $this->ingatkanSatu((int) $data['project_application_id'], $data['catatan'] ?? null);
    }

    private function ingatkanSatu(int $appId, ?string $catatan): RedirectResponse
    {
        $app = ProjectApplication::with('castingProject.admin')->findOrFail($appId);
        $admin = $app->castingProject?->admin;
        abort_unless($admin, 404);

        $kunci = 'pa-'.$app->id;
        $terakhir = $admin->notifications()->where('data->jenis', 'pengingat_sa')->where('data->kunci', $kunci)->latest()->first();
        if ($terakhir && $terakhir->created_at->gt(now()->subDay())) {
            return back()->with('error', 'Admin ini sudah diingatkan untuk tugas ini dalam 24 jam terakhir.');
        }

        $item = collect(AdminRingkasan::tahapan($admin))->flatMap(fn ($t) => $t['daftar'])->first(fn ($i) => $i['app']->id === $app->id);
        abort_unless($item && $item['perlu'], 422);
        $tugas = $item['teks'];
        $url = $item['url'];
        $proyek = $app->castingProject?->nama_produksi;

        $pesan = "Super Admin mengingatkan: {$tugas} di {$proyek}".($catatan ? ". Catatan: {$catatan}" : '');
        $admin->notify(new InAppNotification('Pengingat dari Super Admin', $pesan, $url, ['jenis' => 'pengingat_sa', 'kunci' => $kunci]));

        ActivityLog::record('REMIND_ADMIN', "Super Admin mengingatkan {$admin->name}: {$tugas} di {$proyek}", $app);

        return back()->with('status', "Pengingat terkirim ke {$admin->name}.");
    }

    /** CU.7: satu pengingat untuk sekelompok kandidat Admin+proyek+jenis tugas yang sama, batas 24 jam per kelompok. */
    private function ingatkanKelompok(array $appIds, ?string $catatan): RedirectResponse
    {
        $apps = ProjectApplication::with('castingProject.admin')->whereIn('id', $appIds)->get();
        abort_if($apps->isEmpty(), 404);
        $admin = $apps->first()->castingProject?->admin;
        abort_unless($admin, 404);

        $langkahAdmin = collect(AdminRingkasan::tahapan($admin, PHP_INT_MAX))->flatMap(fn ($t) => $t['daftar']);
        $items = $apps->map(fn ($app) => $langkahAdmin->first(fn ($i) => $i['app']->id === $app->id))
            ->filter(fn ($i) => $i && $i['perlu'])->values();
        abort_if($items->isEmpty(), 422);
        // Catatan minor reviewer: tolak kalau campuran proyek/jenis - form SA seharusnya cuma kirim 1 kelompok.
        abort_if($items->map(fn ($i) => $i['app']->casting_project_id.'|'.$i['jenis'])->unique()->count() > 1, 422);

        $proyekId = $items->first()['app']->casting_project_id;
        $jenis = $items->first()['jenis'];
        $jumlah = $items->count();
        $tugas = $jenis === 'balas_nego' ? 'Counter nego menunggu balasan' : $items->first()['teks'];
        $proyek = $apps->first()->castingProject?->nama_produksi;
        $url = $items->first()['url'];

        $kunci = "pg-{$admin->id}-{$proyekId}-{$jenis}";
        $terakhir = $admin->notifications()->where('data->jenis', 'pengingat_sa')->where('data->kunci', $kunci)->latest()->first();
        if ($terakhir && $terakhir->created_at->gt(now()->subDay())) {
            return back()->with('error', 'Admin ini sudah diingatkan untuk tugas ini dalam 24 jam terakhir.');
        }

        $pesan = "Super Admin mengingatkan: {$tugas} · {$jumlah} Extras di {$proyek}".($catatan ? ". Catatan: {$catatan}" : '');
        $admin->notify(new InAppNotification('Pengingat dari Super Admin', $pesan, $url, ['jenis' => 'pengingat_sa', 'kunci' => $kunci]));

        ActivityLog::record('REMIND_ADMIN', "Super Admin mengingatkan {$admin->name}: {$tugas} ({$jumlah} Extras) di {$proyek}", $apps->first()->castingProject);

        return back()->with('status', "Pengingat terkirim ke {$admin->name}.");
    }

    /**
     * CU.7: kelompokkan tugas "perlu diingatkan" (dari AdminRingkasan::pantauAdmin(),
     * TIDAK diubah) per Admin+proyek+jenis tugas supaya dashboard tidak menampilkan
     * baris kembar per kandidat. Status "sudah diingatkan" dicek per kelompok (kunci
     * 'pg-...'), terpisah dari pengingat per-kandidat ('pa-...') di halaman Pantau Admin.
     */
    private function diingatkanKelompok(Collection $pantauAdmin, Collection $adminsAktif): Collection
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

        return $mentah->map(function ($grup, $kunciGrup) use ($notifTerakhir) {
            [$adminId, $proyekId, $jenis] = explode('|', $kunciGrup, 3);
            $pertama = $grup->sortBy(fn ($t) => $t['sejak']?->timestamp ?? 0)->first();
            $terakhir = $notifTerakhir->get("pg-{$adminId}-{$proyekId}-{$jenis}");
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
                'tugas' => $jenis === 'balas_nego' ? 'Counter nego menunggu balasan' : $pertama['tugas'],
                'jumlah' => $grup->count(),
                'sejak' => $pertama['sejak'],
                'app_ids' => $grup->pluck('app_id')->values(),
                'diingatkan_pada' => $diingatkanPada,
            ];
        })
            ->sortBy(fn ($g) => $g['sejak']?->timestamp ?? 0)
            ->take(5)
            ->values();
    }

    /**
     * CN.2.1.1: Setujui wajib pilih Admin PIC (Korlap opsional). Status jadi
     * disetujui, TAPI lowongan belum dibuka otomatis (status proyek tetap
     * 'ditutup' sampai Admin PIC membuka lewat toggleStatus(), yang digerbang
     * kelengkapan budget per orang - lihat CN.3.3).
     */
    public function accProject(Request $request, CastingProject $castingProject): RedirectResponse
    {
        if ($castingProject->client_request_status !== 'menunggu_acc') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $data = $this->simpanPic($request, $castingProject, ['client_request_status' => 'disetujui']);

        ActivityLog::record(
            'APPROVE_PROJECT_REQUEST',
            "Super Admin menyetujui (ACC) permintaan proyek '{$castingProject->nama_produksi}' dari Client, PIC: ".User::find($data['admin_id'])->name,
            $castingProject
        );

        $castingProject->loadMissing('admin');
        $admin = $castingProject->admin;
        if ($admin) {
            $judulAcc = 'Proyek Baru Disetujui';
            $pesanAcc = "Permintaan proyek '{$castingProject->namaKode()}' telah disetujui Super Admin. Atur budget per orang tiap peran sebelum membuka lowongan.";
            try {
                $admin->notify(new InAppNotification($judulAcc, $pesanAcc, route('admin.projects.edit', $castingProject)));
            } catch (\Throwable) {
            }
        }

        $this->kabariClient($castingProject, 'Pengajuan Proyek Disetujui', "Pengajuan proyek '{$castingProject->namaKode()}' disetujui tim JBTB dan sedang disiapkan.");

        return back()->with('status', "Permintaan proyek '{$castingProject->nama_produksi}' berhasil disetujui (ACC). Proyek kini masuk antrean Admin.");
    }

    /**
     * CR.2.3: "Tetapkan PIC" - untuk proyek yang SUDAH disetujui tapi belum
     * punya Admin PIC (CastingProject::tanpaPicClient()). Reuse validasi &
     * cara assign PIC/Korlap yang sama dengan accProject() lewat simpanPic(),
     * bukan logika keputusan baru.
     */
    public function tetapkanPic(Request $request, CastingProject $castingProject): RedirectResponse
    {
        if (! CastingProject::tanpaPicClient()->whereKey($castingProject->id)->exists()) {
            return back()->with('error', 'Proyek ini belum bisa ditetapkan PIC-nya.');
        }
        if ($castingProject->admin_id) {
            return back()->with('error', 'Proyek ini sudah punya Admin PIC.');
        }

        $data = $this->simpanPic($request, $castingProject);

        ActivityLog::record(
            'ASSIGN_PIC_PROJECT',
            "Super Admin menetapkan Admin PIC proyek '{$castingProject->nama_produksi}': ".User::find($data['admin_id'])->name,
            $castingProject
        );

        $castingProject->loadMissing('admin');
        if ($admin = $castingProject->admin) {
            try {
                $admin->notify(new InAppNotification('Ditugaskan sebagai PIC Proyek', "Anda ditetapkan sebagai Admin PIC proyek '{$castingProject->namaKode()}'.", route('admin.projects.show', $castingProject)));
            } catch (\Throwable) {
            }
        }

        return back()->with('status', "Admin PIC untuk '{$castingProject->nama_produksi}' berhasil ditetapkan.");
    }

    /** Reuse validasi + simpan admin_id/korlap_id, dipakai accProject() & tetapkanPic(). */
    private function simpanPic(Request $request, CastingProject $castingProject, array $tambahan = []): array
    {
        $data = $request->validate([
            'admin_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN)->where('status', 'aktif')],
            'korlap_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_KORLAP)->where('status', 'aktif')],
        ], [
            'admin_id.required' => 'Pilih Admin PIC proyek ini dulu.',
        ]);

        $castingProject->update(['admin_id' => $data['admin_id'], ...$tambahan]);

        $castingProject->adminAssignments()->firstOrCreate(
            ['user_id' => $data['admin_id']],
            ['assigned_by' => $request->user()->id, 'status_log' => 'berjalan']
        );
        if (! empty($data['korlap_id'])) {
            $castingProject->adminAssignments()->firstOrCreate(
                ['user_id' => $data['korlap_id']],
                ['assigned_by' => $request->user()->id, 'status_log' => 'berjalan']
            );
        }

        return $data;
    }

    /**
     * CN.2.1.2: Minta revisi - wajib catatan, disimpan di alasan_tolak (label
     * UI "Catatan dari JBTB"). Client mengubah lalu mengajukan ulang.
     */
    public function mintaRevisiProject(Request $request, CastingProject $castingProject): RedirectResponse
    {
        if ($castingProject->client_request_status !== 'menunggu_acc') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }
        $data = $request->validate(['alasan_tolak' => ['required', 'string', 'max:500']]);

        $castingProject->update([
            'client_request_status' => 'perlu_revisi',
            'alasan_tolak' => $data['alasan_tolak'],
        ]);

        $this->kabariClient($castingProject, 'Pengajuan Proyek Perlu Revisi', "Pengajuan proyek '{$castingProject->namaKode()}' perlu direvisi. Catatan dari JBTB: {$data['alasan_tolak']}");

        ActivityLog::record(
            'REQUEST_REVISION_PROJECT',
            "Super Admin meminta revisi permintaan proyek '{$castingProject->nama_produksi}' dari Client",
            $castingProject
        );

        return back()->with('status', "Permintaan revisi untuk '{$castingProject->nama_produksi}' telah dikirim ke Client.");
    }

    public function rejectProject(Request $request, CastingProject $castingProject): RedirectResponse
    {
        if ($castingProject->client_request_status !== 'menunggu_acc') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }
        $data = $request->validate(['alasan_tolak' => ['required', 'string', 'max:500']]);

        $castingProject->update([
            'client_request_status' => 'ditolak',
            'status' => 'ditutup',
            'alasan_tolak' => $data['alasan_tolak'],
        ]);

        $this->kabariClient($castingProject, 'Pengajuan Proyek Ditolak', "Pengajuan proyek '{$castingProject->namaKode()}' ditolak. Alasan: {$data['alasan_tolak']}");

        ActivityLog::record(
            'REJECT_PROJECT_REQUEST',
            "Super Admin menolak permintaan proyek '{$castingProject->nama_produksi}' dari Client",
            $castingProject
        );

        return back()->with('status', "Permintaan proyek '{$castingProject->nama_produksi}' telah ditolak.");
    }

    private function kabariClient(CastingProject $castingProject, string $judul, string $pesan): void
    {
        try {
            $castingProject->client?->notify(new InAppNotification($judul, $pesan, route('client.dashboard')));
        } catch (\Throwable) {
        }
    }
}
