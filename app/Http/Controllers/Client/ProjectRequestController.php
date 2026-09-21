<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
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
        $myRequests = CastingProject::where('diajukan_oleh_client_id', auth()->id())
            ->latest()
            ->get();

        return view('cd.projects.request', compact('myRequests'));
    }

    /**
     * Simpan brief proyek dari Client untuk di-ACC oleh Super Admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_produksi' => ['required', 'string', 'max:255'],
            'client_ph' => ['nullable', 'string', 'max:255'],
            'deadline' => ['required', 'date', 'after_or_equal:today'],
            'kuota' => ['required', 'integer', 'min:1'],
            'brief_catatan' => ['required', 'string', 'max:2000'],
            'poster_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_path' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);

        $posterPath = $request->hasFile('poster_path')
            ? $request->file('poster_path')->store('posters', 'public')
            : null;

        $coverPath = $request->hasFile('cover_path')
            ? $request->file('cover_path')->store('covers', 'public')
            : null;

        $project = CastingProject::create([
            'nama_produksi' => $data['nama_produksi'],
            'client_ph' => $data['client_ph'] ?? $request->user()->name,
            'poster_path' => $posterPath,
            'cover_path' => $coverPath,
            'share_token' => Str::random(32),
            'deadline' => $data['deadline'],
            'kuota' => $data['kuota'],
            'brief_catatan' => $data['brief_catatan'],
            'diajukan_oleh_client_id' => $request->user()->id,
            'client_request_status' => 'menunggu_acc',
            'status' => 'ditutup',
            'is_urgent' => false,
        ]);

        // Otomatis assign client ini ke proyek yang diajukan
        $project->cdAssignments()->create(['cd_user_id' => $request->user()->id]);

        ActivityLog::record(
            'SUBMIT_PROJECT_REQUEST',
            "Client {$request->user()->name} mengajukan brief proyek casting baru: '{$project->nama_produksi}'",
            $project
        );

        return redirect()->route('cd.dashboard')->with('status', 'Brief permintaan proyek berhasil diajukan! Menunggu peninjauan & persetujuan Super Admin.');
    }
}
