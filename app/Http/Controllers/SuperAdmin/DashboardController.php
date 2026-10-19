<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\StaffPayroll;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Services\KeuanganService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    const PRESET = ['bulan' => 'Bulan ini', '3bulan' => '3 bulan', 'tahun' => 'Tahun ini'];

    public function __construct(
        protected KeuanganService $keuanganService
    ) {}

    /**
     * BD.3.1: preset atau custom dari–sampai (custom menang kalau dua-duanya valid).
     */
    private function periode(Request $request): array
    {
        $tgl = fn (string $k) => rescue(fn () => Carbon::createFromFormat('!Y-m-d', (string) $request->query($k)), null, false) ?: null;
        [$dari, $sampai] = [$tgl('dari'), $tgl('sampai')];

        if ($dari && $sampai) {
            return $dari->gt($sampai) ? [$sampai, $dari, null] : [$dari, $sampai, null];
        }

        $preset = array_key_exists((string) $request->query('periode'), self::PRESET) ? $request->query('periode') : 'bulan';

        return match ($preset) {
            '3bulan' => [now()->subMonths(2)->startOfMonth(), now()->endOfMonth(), $preset],
            'tahun' => [now()->startOfYear(), now()->endOfYear(), $preset],
            default => [now()->startOfMonth(), now()->endOfMonth(), $preset],
        };
    }

    public function index(Request $request)
    {
        [$dari, $sampai, $preset] = $this->periode($request);
        $jumlahHari = (int) $dari->copy()->startOfDay()->diffInDays($sampai->copy()->startOfDay()) + 1;

        $pendingRequests = CastingProject::where('client_request_status', 'menunggu_acc')
            ->with('client', 'classes', 'shootingDates', 'attachments')->latest()->get();
        $adminsAktif = User::where('role', User::ROLE_ADMIN)->where('status', 'aktif')->orderBy('name')->get();
        $korlapsAktif = User::where('role', User::ROLE_KORLAP)->where('status', 'aktif')->orderBy('name')->get();
        $sengketa = Payment::where('status', 'disengketakan')
            ->with('projectApplication.extras.user', 'projectApplication.castingProject:id,nama_produksi')->latest()->get();
        $honorStaf = (object) [
            'jumlah' => StaffPayroll::where('status_bayar', '!=', 'sudah')->count(),
            'total' => $this->keuanganService->totalHonorStafBelumDiproses(),
        ];
        $tanpaClient = CastingProject::tanpaPicClient()->count();
        $invoiceBelumLunas = Invoice::where('status_bayar', '!=', 'lunas')
            ->with('castingProject.classes')->latest()->get()
            ->each(fn (Invoice $i) => $i->nilai = (float) ($i->nominal ?? $this->keuanganService->nilaiInvoice($i->castingProject)));

        $tahapQuery = fn (string $tahap) => CastingProject::diTahap($tahap)
            ->when($tahap !== 'menunggu_acc', fn ($q) => $q->shootingDalam($dari, $sampai));
        $statusProyek = collect(CastingProject::TAHAP)->map(fn ($label, $tahap) => $tahapQuery($tahap)->count());
        $proyekPerTahap = collect(CastingProject::TAHAP)->map(fn ($label, $tahap) => $tahapQuery($tahap)
            ->with('client:id,name', 'shootingDates')
            ->when($tahap === 'selesai',
                fn ($q) => $q->withMax('shootingDates as tanggal_acuan', 'tanggal')->orderByDesc('tanggal_acuan'),
                fn ($q) => $q->withMin(['shootingDates as tanggal_acuan' => fn ($d) => $d->whereDate('tanggal', '>=', today())], 'tanggal'))
            ->when(in_array($tahap, ['mendatang', 'berjalan']), fn ($q) => $q->orderByRaw('tanggal_acuan is null')->orderBy('tanggal_acuan'))
            ->when($tahap === 'menunggu_acc', fn ($q) => $q->latest())
            ->take(5)->get());
        $tabAwal = collect(['berjalan', 'mendatang', 'menunggu_acc', 'selesai'])->first(fn ($t) => $statusProyek[$t] > 0, 'berjalan');

        $bulan = rescue(fn () => Carbon::createFromFormat('!Y-m', (string) $request->query('bulan')), null, false) ?: now();
        $jadwal = EventShootingDate::whereDate('tanggal', '>=', $bulan->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY))
            ->whereDate('tanggal', '<=', $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY))
            ->whereHas('castingProject', fn ($q) => $q->where('client_request_status', 'disetujui'))
            ->with(['castingProject' => fn ($q) => $q->select('id', 'nama_produksi')->withCount(['applications as extras_count' => fn ($a) => $a
                ->whereIn('status_partisipasi', [...ProjectApplication::STATUS_AKTIF, 'selesai_produksi'])])])
            ->withCount(['attendances as hadir_count' => fn ($q) => $q->where('status', 'hadir')])
            ->get()
            ->each(function (EventShootingDate $e) {
                $e->nama_produksi = $e->castingProject?->nama_produksi;
                $e->jumlah_extras = (int) $e->castingProject?->extras_count;
                $e->absensi = "{$e->hadir_count}/{$e->jumlah_extras} hadir";
                $e->url_proyek = route('admin.projects.show', $e->casting_project_id);
                $e->url_absensi = route('admin.attendance.index', ['project' => $e->casting_project_id, 'tanggal' => $e->id]);
            });

        $akunPerRole = User::where('status', 'aktif')->selectRaw('role, count(*) as jumlah')->groupBy('role')->pluck('jumlah', 'role');
        $clientBelumGantiPassword = User::where('role', 'client')->where('wajib_ganti_password', true)->count();

        return view('super-admin.dashboard', compact(
            'dari', 'sampai', 'preset', 'jumlahHari',
            'pendingRequests', 'adminsAktif', 'korlapsAktif', 'sengketa', 'honorStaf', 'invoiceBelumLunas', 'tanpaClient',
            'statusProyek', 'proyekPerTahap', 'tabAwal', 'bulan', 'jadwal',
            'akunPerRole', 'clientBelumGantiPassword'
        ));
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
