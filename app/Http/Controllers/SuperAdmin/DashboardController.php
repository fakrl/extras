<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\Invoice;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Services\KeuanganService;
use App\Support\AdminRingkasan;
use App\Support\Periode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    public function index(Request $request)
    {
        [$dari, $sampai, $preset] = Periode::dariSesi($request, 'periode_sa', '30');
        $jumlahHari = (int) $dari->copy()->startOfDay()->diffInDays($sampai->copy()->startOfDay()) + 1;

        // CO.1: "Perlu Tindakan" SA hanya tugas SA, dikelompokkan per jenis.
        // Pengajuan yang sudah direvisi Client dibedakan dari pengajuan baru
        // lewat alasan_tolak terisi (diisi saat Minta Revisi, tidak dihapus
        // saat Client mengajukan ulang) - murah, tanpa kolom/tabel baru.
        $pengajuanBaru = CastingProject::where('client_request_status', 'menunggu_acc')->whereNull('alasan_tolak')
            ->with('client', 'classes', 'shootingDates', 'attachments')->latest()->get();
        $pengajuanRevisi = CastingProject::where('client_request_status', 'menunggu_acc')->whereNotNull('alasan_tolak')
            ->with('client', 'classes', 'shootingDates', 'attachments')->latest()->get();
        $adminsAktif = User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get();
        $korlapsAktif = User::where('role', User::ROLE_KORLAP)->where('status', 'aktif')->orderBy('name')->get();
        $honorStaf = (object) [
            'jumlah' => StaffPayroll::where('status_bayar', '!=', 'sudah')->count(),
            'total' => $this->keuanganService->totalHonorStafBelumDiproses(),
        ];
        $tanpaPic = CastingProject::tanpaPicClient()->latest()->get();
        $invoiceBelumLunas = Invoice::where('status_bayar', '!=', 'lunas')
            ->with('castingProject.classes')->latest()->get()
            ->each(fn (Invoice $i) => $i->nilai = (float) ($i->nominal ?? $this->keuanganService->nilaiInvoice($i->castingProject)));
        // Dasar "> 7 hari": updated_at (bukan created_at) supaya akun yang direset
        // passwordnya belakangan (AdminManagementController::resetPassword) dihitung
        // sejak reset, bukan sejak akun dibuat.
        $akunPerhatian = User::where('role', 'client')->where('wajib_ganti_password', true)
            ->where('updated_at', '<=', now()->subDays(7))->count();

        $jumlahTindakan = $pengajuanBaru->count() + $pengajuanRevisi->count() + $tanpaPic->count() + $akunPerhatian;
        $pengajuanMendesak = $pengajuanBaru->merge($pengajuanRevisi)->contains(fn ($p) => $p->created_at?->lte(now()->subDays(2)));

        // CO.2: Pantau Admin - tugas tertunda per Admin aktif, dari AdminRingkasan::tahapan() yang sudah ada.
        $pantauAdmin = $this->pantauAdmin($adminsAktif);
        $pantauTotal = $pantauAdmin->sum(fn ($p) => $p['tugas']->count());
        $pantauMendesak = $pantauAdmin->contains(fn ($p) => $p['tugas']->contains(fn ($t) => $t['sejak']?->lte(now()->subDays(3))));

        $tahapQuery = fn (string $tahap, $d, $s) => CastingProject::diTahap($tahap)
            ->when($tahap !== 'menunggu_acc', fn ($q) => $q->shootingDalam($d, $s));
        $statusProyek = collect(CastingProject::TAHAP)->map(fn ($label, $tahap) => $tahapQuery($tahap, $dari, $sampai)->count());
        $proyekPerTahap = collect(CastingProject::TAHAP)->map(fn ($label, $tahap) => $tahapQuery($tahap, $dari, $sampai)
            ->with('client:id,name', 'shootingDates')
            ->when($tahap === 'selesai',
                fn ($q) => $q->withMax('shootingDates as tanggal_acuan', 'tanggal')->orderByDesc('tanggal_acuan'),
                fn ($q) => $q->withMin(['shootingDates as tanggal_acuan' => fn ($d) => $d->whereDate('tanggal', '>=', today())], 'tanggal'))
            ->when(in_array($tahap, ['mendatang', 'berjalan']), fn ($q) => $q->orderByRaw('tanggal_acuan is null')->orderBy('tanggal_acuan'))
            ->when($tahap === 'menunggu_acc', fn ($q) => $q->latest())
            ->take(5)->get());
        $tabAwal = collect(['berjalan', 'mendatang', 'menunggu_acc', 'selesai'])->first(fn ($t) => $statusProyek[$t] > 0, 'berjalan');

        // CQ.4.1: pertumbuhan % Status Proyek vs periode sebelumnya, panjang sama. Menunggu ACC tidak ikut filter → tidak punya pertumbuhan.
        $sampaiSebelum = $dari->copy()->subDay();
        $dariSebelum = $sampaiSebelum->copy()->subDays($jumlahHari - 1);
        $pertumbuhan = collect(CastingProject::TAHAP)->map(function ($label, $tahap) use ($tahapQuery, $dariSebelum, $sampaiSebelum, $statusProyek) {
            if ($tahap === 'menunggu_acc') {
                return null;
            }
            $sebelum = $tahapQuery($tahap, $dariSebelum, $sampaiSebelum)->count();
            $sekarang = $statusProyek[$tahap];

            return $sebelum > 0 ? (int) round((($sekarang - $sebelum) / $sebelum) * 100) : ($sekarang > 0 ? 100 : 0);
        });

        // CQ.2/CQ.4: satu agenda mengikuti filter periode yang sama (gantikan jadwalRingkas + jadwal lama).
        $agendaEvents = $this->jadwalEvents($dari, $sampai);

        $akunPerRole = User::where('status', 'aktif')->selectRaw('role, count(*) as jumlah')->groupBy('role')->pluck('jumlah', 'role');

        return view('super-admin.dashboard', compact(
            'dari', 'sampai', 'preset', 'jumlahHari',
            'pengajuanBaru', 'pengajuanRevisi', 'pengajuanMendesak', 'adminsAktif', 'korlapsAktif',
            'honorStaf', 'invoiceBelumLunas', 'tanpaPic', 'akunPerhatian', 'jumlahTindakan',
            'pantauAdmin', 'pantauTotal', 'pantauMendesak',
            'statusProyek', 'proyekPerTahap', 'tabAwal', 'pertumbuhan',
            'agendaEvents',
            'akunPerRole'
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
     * CO.2.1: per Admin aktif, tugas tertunda (reuse AdminRingkasan::tahapan(), tanpa
     * hitungan baru). Maks 5 tugas terlama per Admin, sama urutan dengan dashboard Admin.
     */
    private function pantauAdmin(Collection $admins): Collection
    {
        return $admins->map(function (User $a) {
            $tugas = collect(AdminRingkasan::tahapan($a))
                ->flatMap(fn ($t) => $t['daftar'])
                ->filter(fn ($i) => $i['perlu'] && $i['app']->castingProject?->admin_id === $a->id)
                ->sortBy(fn ($i) => $i['sejak']?->timestamp ?? 0)
                ->take(5)
                ->map(function ($i) use ($a) {
                    $kunci = 'pa-'.$i['app']->id;
                    $terakhir = $a->notifications()->where('data->jenis', 'pengingat_sa')->where('data->kunci', $kunci)->latest()->first();

                    return [
                        'app_id' => $i['app']->id,
                        'proyek' => $i['app']->castingProject?->nama_produksi,
                        'tugas' => $i['teks'],
                        'sejak' => $i['sejak'],
                        'url' => $i['url'],
                        'diingatkan_pada' => $terakhir && $terakhir->created_at->gt(now()->subDay()) ? $terakhir->created_at : null,
                    ];
                })->values();

            return ['admin' => $a, 'tugas' => $tugas];
        })->filter(fn ($p) => $p['tugas']->isNotEmpty())->values();
    }

    /** CO.2.2/CO.2.3: ingatkan Admin PIC, notif in-app saja, maks 1x/24 jam per tugas. */
    public function ingatkanAdmin(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'project_application_id' => ['required', 'integer', Rule::exists('project_applications', 'id')],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        $app = ProjectApplication::with('castingProject.admin')->findOrFail($data['project_application_id']);
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

        $pesan = "Super Admin mengingatkan: {$tugas} di {$proyek}".(($data['catatan'] ?? null) ? ". Catatan: {$data['catatan']}" : '');
        $admin->notify(new InAppNotification('Pengingat dari Super Admin', $pesan, $url, ['jenis' => 'pengingat_sa', 'kunci' => $kunci]));

        ActivityLog::record('REMIND_ADMIN', "Super Admin mengingatkan {$admin->name}: {$tugas} di {$proyek}", $app);

        return back()->with('status', "Pengingat terkirim ke {$admin->name}.");
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

        $data = $request->validate([
            'admin_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_ADMIN)->where('status', 'aktif')],
            'korlap_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', User::ROLE_KORLAP)->where('status', 'aktif')],
        ], [
            'admin_id.required' => 'Pilih Admin PIC proyek ini dulu.',
        ]);

        $castingProject->update([
            'client_request_status' => 'disetujui',
            'admin_id' => $data['admin_id'],
        ]);

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
