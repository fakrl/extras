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
            ->with('client')->latest()->get();
        $sengketa = Payment::where('status', 'disengketakan')
            ->with('projectApplication.extras.user', 'projectApplication.castingProject:id,nama_produksi')->latest()->get();
        $honorStaf = (object) [
            'jumlah' => StaffPayroll::where('status_bayar', '!=', 'sudah')->count(),
            'total' => $this->keuanganService->totalHonorStafBelumDiproses(),
        ];
        $tanpaClient = CastingProject::whereNull('client_id')->count();
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

        $uang = $this->keuanganService->ringkasanPeriode($dari, $sampai);

        $bulan = rescue(fn () => Carbon::createFromFormat('!Y-m', (string) $request->query('bulan')), null, false) ?: now();
        $jadwal = EventShootingDate::whereDate('tanggal', '>=', $bulan->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY))
            ->whereDate('tanggal', '<=', $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY))
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

        $akunPerRole = User::selectRaw('role, count(*) as jumlah')->groupBy('role')->pluck('jumlah', 'role');
        $clientBelumGantiPassword = User::where('role', 'client')->where('wajib_ganti_password', true)->count();

        return view('super-admin.dashboard', compact(
            'dari', 'sampai', 'preset', 'jumlahHari',
            'pendingRequests', 'sengketa', 'honorStaf', 'invoiceBelumLunas', 'tanpaClient',
            'statusProyek', 'proyekPerTahap', 'tabAwal', 'uang', 'bulan', 'jadwal',
            'akunPerRole', 'clientBelumGantiPassword'
        ));
    }

    public function accProject(CastingProject $castingProject): RedirectResponse
    {
        if ($castingProject->client_request_status !== 'menunggu_acc') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }
        $castingProject->update([
            'client_request_status' => 'disetujui',
            'status' => 'dibuka',
        ]);

        ActivityLog::record(
            'APPROVE_PROJECT_REQUEST',
            "Super Admin menyetujui (ACC) permintaan proyek '{$castingProject->nama_produksi}' dari Client",
            $castingProject
        );

        $castingProject->loadMissing('admin');
        $admin = $castingProject->admin;
        if ($admin) {
            $judulAcc = 'Proyek Baru Disetujui';
            $pesanAcc = "Permintaan proyek '{$castingProject->nama_produksi}' telah disetujui Super Admin. Silakan lengkapi detail proyek.";
            try {
                $admin->notify(new InAppNotification($judulAcc, $pesanAcc, route('admin.projects.edit', $castingProject)));
            } catch (\Throwable) {
            }
        }

        $this->kabariClient($castingProject, 'Pengajuan Proyek Disetujui', "Pengajuan proyek '{$castingProject->nama_produksi}' disetujui tim JBTB dan sedang disiapkan.");

        return back()->with('status', "Permintaan proyek '{$castingProject->nama_produksi}' berhasil disetujui (ACC). Proyek kini masuk antrean Admin.");
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

        $this->kabariClient($castingProject, 'Pengajuan Proyek Ditolak', "Pengajuan proyek '{$castingProject->nama_produksi}' ditolak. Alasan: {$data['alasan_tolak']}");

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
