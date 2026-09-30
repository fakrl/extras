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

        return view('client.projects.request', compact('myRequests'));
    }

    /**
     * Simpan brief proyek dari Client untuk di-ACC oleh Super Admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_produksi' => ['required', 'string', 'max:255'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'kuota' => ['required', 'integer', 'min:1'],
            'brief_catatan' => ['required', 'string', 'max:2000'],
            'poster_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'files' => ['nullable', 'array'],
            'files.*' => ProjectAttachment::RULE_FILE,
        ]);

        $posterPath = $request->hasFile('poster_path')
            ? $request->file('poster_path')->store('posters', 'public')
            : null;

        $project = CastingProject::create([
            'nama_produksi' => $data['nama_produksi'],
            'poster_path' => $posterPath,
            'share_token' => Str::random(32),
            'deadline' => $data['deadline'],
            'kuota' => $data['kuota'],
            'brief_catatan' => $data['brief_catatan'],
            'client_id' => $request->user()->id,
            'client_request_status' => 'menunggu_acc',
            'status' => 'ditutup',
            'is_urgent' => false,
        ]);

        ActivityLog::record(
            'SUBMIT_PROJECT_REQUEST',
            "Client {$request->user()->name} mengajukan brief proyek casting baru: '{$project->nama_produksi}'",
            $project
        );

        if (! empty($data['files'])) {
            ProjectAttachment::unggah($project, $data['files'], $request->user());
        }

        $client = $request->user();
        User::where('role', 'super_admin')->get()
            ->each(function ($sa) use ($project, $client) {
                $judul5 = 'Ada Permintaan Proyek Baru';
                $pesan5 = "Client '{$client->name}' mengajukan permintaan proyek baru: '{$project->nama_produksi}'.";
                try {
                    $sa->notify(new InAppNotification($judul5, $pesan5, route('super-admin.dashboard')));
                } catch (\Throwable) {
                }
            });

        return redirect()->route('client.dashboard')->with('status', 'Brief permintaan proyek berhasil diajukan! Menunggu peninjauan & persetujuan tim JBTB.');
    }
}
