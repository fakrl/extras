<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\ProjectAttachment;
use App\Models\User;
use App\Notifications\InAppNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectRequestController extends Controller
{
    /**
     * Tampilkan form pengajuan brief proyek baru oleh Client (Pintu 1).
     */
    public function create()
    {
        $myRequests = CastingProject::query()->milikClient(auth()->user())->whereNotNull('brief_catatan')
            ->latest()
            ->get();

        return view('client.projects.request', ['myRequests' => $myRequests, 'editing' => null]);
    }

    /**
     * CN.2.2: Client mengubah pengajuan yang diminta revisi, sebelum mengajukan ulang.
     */
    public function edit(CastingProject $castingProject)
    {
        abort_unless($castingProject->milikClient(auth()->user()) && $castingProject->client_request_status === 'perlu_revisi', 404);

        $castingProject->load('classes', 'shootingDates');

        return view('client.projects.request', ['myRequests' => collect(), 'editing' => $castingProject]);
    }

    /**
     * Simpan brief proyek dari Client untuk di-ACC oleh Super Admin.
     * CN.1: peran, tanggal shooting + lokasi, dan budget proyek total diisi di sini,
     * SEBELUM sampai ke Super Admin. Budget per orang tiap peran dikosongkan dulu.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $project = CastingProject::create([
            'nama_produksi' => $data['nama_produksi'],
            'poster_path' => $this->simpanPoster($request),
            'share_token' => Str::random(32),
            'deadline' => $data['deadline'],
            'kuota' => $this->totalKuota($data['kelas']),
            'budget_total' => $data['budget_total'],
            'brief_catatan' => $data['brief_catatan'],
            'client_id' => $request->user()->id,
            'client_request_status' => 'menunggu_acc',
            'status' => 'ditutup',
            'is_urgent' => false,
        ]);

        $this->simpanPeranDanJadwal($project, $data);

        ActivityLog::record(
            'SUBMIT_PROJECT_REQUEST',
            "Client {$request->user()->name} mengajukan brief proyek casting baru: '{$project->nama_produksi}'",
            $project
        );

        if (! empty($data['files'])) {
            ProjectAttachment::unggah($project, $data['files'], $request->user());
        }

        $this->kabariSuperAdmin('Ada Permintaan Proyek Baru', "Client '{$request->user()->name}' mengajukan permintaan proyek baru: '{$project->namaKode()}'.");

        return redirect()->route('client.dashboard')->with('status', 'Brief permintaan proyek berhasil diajukan! Menunggu peninjauan & persetujuan tim JBTB.');
    }

    /**
     * CN.2.2: Client mengajukan ulang pengajuan yang diminta revisi -> balik ke menunggu_acc.
     */
    public function update(Request $request, CastingProject $castingProject): RedirectResponse
    {
        abort_unless($castingProject->milikClient($request->user()) && $castingProject->client_request_status === 'perlu_revisi', 404);

        $data = $request->validate($this->rules());

        $castingProject->update([
            'nama_produksi' => $data['nama_produksi'],
            'poster_path' => $this->simpanPoster($request) ?? $castingProject->poster_path,
            'deadline' => $data['deadline'],
            'kuota' => $this->totalKuota($data['kelas']),
            'budget_total' => $data['budget_total'],
            'brief_catatan' => $data['brief_catatan'],
            'client_request_status' => 'menunggu_acc',
        ]);

        $castingProject->classes()->delete();
        $castingProject->shootingDates()->delete();
        $this->simpanPeranDanJadwal($castingProject, $data);

        ActivityLog::record(
            'SUBMIT_PROJECT_REQUEST',
            "Client {$request->user()->name} mengajukan ulang proyek casting '{$castingProject->nama_produksi}' setelah revisi",
            $castingProject
        );

        if (! empty($data['files'])) {
            ProjectAttachment::unggah($castingProject, $data['files'], $request->user());
        }

        $this->kabariSuperAdmin('Pengajuan Proyek Diajukan Ulang', "Client '{$request->user()->name}' mengajukan ulang proyek '{$castingProject->namaKode()}' setelah revisi.");

        return redirect()->route('client.dashboard')->with('status', 'Pengajuan berhasil diajukan ulang! Menunggu peninjauan tim JBTB.');
    }

    private function rules(): array
    {
        return [
            'nama_produksi' => ['required', 'string', 'max:255'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'brief_catatan' => ['required', 'string', 'max:2000'],
            'budget_total' => ['required', 'numeric', 'min:0.01'],
            'poster_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'files' => ['nullable', 'array'],
            'files.*' => ProjectAttachment::RULE_FILE,
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*.nama_kelas' => ['required', 'string', 'max:255'],
            'kelas.*.kuota_kelas' => ['required', 'integer', 'min:1'],
            'kelas.*.kriteria' => ['nullable', 'string', 'max:500'],
            'tanggal_shooting' => ['required', 'array', 'min:1'],
            'tanggal_shooting.*.tanggal' => ['required', 'date', 'after:deadline'],
            'tanggal_shooting.*.lokasi' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function totalKuota(array $kelas): int
    {
        return (int) collect($kelas)->sum('kuota_kelas');
    }

    private function simpanPoster(Request $request): ?string
    {
        return $request->hasFile('poster_path') ? $request->file('poster_path')->store('posters', 'public') : null;
    }

    private function simpanPeranDanJadwal(CastingProject $project, array $data): void
    {
        foreach ($data['kelas'] as $kelas) {
            $project->classes()->create([
                'nama_kelas' => $kelas['nama_kelas'],
                'kriteria' => $kelas['kriteria'] ?? null,
                'kuota_kelas' => $kelas['kuota_kelas'],
            ]);
        }

        foreach ($data['tanggal_shooting'] as $tanggal) {
            $project->shootingDates()->create([
                'tanggal' => $tanggal['tanggal'],
                'lokasi' => $tanggal['lokasi'] ?? null,
            ]);
        }
    }

    private function kabariSuperAdmin(string $judul, string $pesan): void
    {
        User::where('role', 'super_admin')->get()
            ->each(function ($sa) use ($judul, $pesan) {
                try {
                    $sa->notify(new InAppNotification($judul, $pesan, route('super-admin.dashboard')));
                } catch (\Throwable) {
                }
            });
    }
}
