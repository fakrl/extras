<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\EventShootingDate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CS.1/CS.2: callsheet per tanggal shooting - pratinjau, salin format WA,
 * unduh PDF. Akses: Admin dikelolaOleh() + Korlap ditugaskan (lewat relasi
 * yang sama) + Super Admin (lihat). PDF dibuat on-demand, TIDAK disimpan ke
 * disk (beda dari kontrak/invoice yang di-cache lewat PdfGeneratorService).
 */
class CallsheetController extends Controller
{
    public function show(Request $request, CastingProject $castingProject, string $tanggal)
    {
        $this->otorisasi($request, $castingProject);
        $data = $this->bangunData($castingProject, $this->cariTanggal($castingProject, $tanggal));

        return view('admin.callsheet.show', $data + [
            'teksWa' => $this->teksWa($data),
            // CS.1.3: badge TTD & link kelola proyek cuma buat Admin/SA, Korlap cuma lihat.
            'bolehKelola' => ! $request->user()->isKorlap(),
        ]);
    }

    public function pdf(Request $request, CastingProject $castingProject, string $tanggal): Response
    {
        $this->otorisasi($request, $castingProject);
        $shootingDate = $this->cariTanggal($castingProject, $tanggal);
        $data = $this->bangunData($castingProject, $shootingDate);

        ActivityLog::record(
            'CALLSHEET_EXPORT',
            "{$request->user()->label()} {$request->user()->name} mengunduh PDF callsheet proyek '{$castingProject->nama_produksi}' tanggal {$shootingDate->tanggal->toDateString()}",
            $castingProject,
            ['format' => 'pdf', 'tanggal' => $shootingDate->tanggal->toDateString()]
        );

        $nama = "Callsheet-{$castingProject->kode_proyek}-{$shootingDate->tanggal->toDateString()}.pdf";

        return Pdf::loadView('admin.callsheet.pdf', $data)->download($nama);
    }

    /** CS.2.3: dipanggil via fetch() setelah navigator.clipboard.writeText() sukses di sisi klien. */
    public function salin(Request $request, CastingProject $castingProject, string $tanggal): Response
    {
        $this->otorisasi($request, $castingProject);
        $shootingDate = $this->cariTanggal($castingProject, $tanggal);

        ActivityLog::record(
            'CALLSHEET_EXPORT',
            "{$request->user()->label()} {$request->user()->name} menyalin format WA callsheet proyek '{$castingProject->nama_produksi}' tanggal {$shootingDate->tanggal->toDateString()}",
            $castingProject,
            ['format' => 'wa', 'tanggal' => $shootingDate->tanggal->toDateString()]
        );

        return response()->noContent();
    }

    private function otorisasi(Request $request, CastingProject $castingProject): void
    {
        $user = $request->user();
        abort_unless($user->isSuperAdmin() || $castingProject->dikelolaOleh($user), 403);
    }

    private function cariTanggal(CastingProject $castingProject, string $tanggal): EventShootingDate
    {
        return $castingProject->shootingDates()->whereDate('tanggal', $tanggal)->firstOrFail();
    }

    /**
     * CS.1.4: TIDAK boleh ikut: nomor WA/NIK/nama asli/rekening Extras, fee,
     * budget, nama Client kalau disembunyikan. Extras cuma lewat alias
     * (`@username`). Talent yang masuk: status lolos & kontrak_ditandatangani saja.
     */
    private function bangunData(CastingProject $castingProject, EventShootingDate $shootingDate): array
    {
        $castingProject->loadMissing('adminAssignments.user');

        $peran = $castingProject->applications()
            ->whereIn('status_partisipasi', ['lolos', 'kontrak_ditandatangani'])
            ->with('extras.user', 'castingProjectClass')
            ->get()
            ->groupBy(fn ($a) => $a->casting_project_class_id ?? 0)
            ->map(function ($apps) {
                $class = $apps->first()->castingProjectClass;
                $jamDefault = $class?->jam_callingan;
                $sceneDefault = $class?->keterangan_scene;

                $talents = $apps->values()->map(function ($app, $i) use ($jamDefault, $sceneDefault) {
                    $catatan = array_filter([
                        $app->jam_callingan && $app->jam_callingan !== $jamDefault ? "kumpul {$app->jam_callingan}" : null,
                        $app->keterangan_scene && $app->keterangan_scene !== $sceneDefault ? $app->keterangan_scene : null,
                    ]);

                    return [
                        'no' => $i + 1,
                        'nama' => $app->extras->user->username ? '@'.$app->extras->user->username : '(belum isi username)',
                        'catatan' => implode(', ', $catatan),
                        'kontrak_belum_ttd' => $app->status_partisipasi !== 'kontrak_ditandatangani',
                    ];
                })->all();

                return [
                    'nama' => $class?->nama_kelas ?? 'Umum',
                    'jam_kumpul' => $jamDefault,
                    'kostum_scene' => $sceneDefault,
                    'kontinuitas' => ($class?->tipe_continuity ?? 'free') === 'continuity' ? 'Berlanjut (beberapa hari)' : 'Sehari saja',
                    'talents' => $talents,
                ];
            })
            ->values()->all();

        $korlap = $castingProject->adminAssignments
            ->filter(fn ($a) => $a->user?->isKorlap())
            ->map(fn ($a) => ['nama' => $a->user->name, 'wa' => $a->user->nomorWaInternasional()])
            ->values()->all();

        $warnings = array_values(array_filter([
            $shootingDate->lokasi ? null : 'Lokasi belum diisi',
            $shootingDate->jam_mulai ? null : 'Jam callsheet belum diisi',
            collect($peran)->contains(fn ($p) => ! $p['jam_kumpul']) ? 'Jam kumpul salah satu peran belum diisi' : null,
        ]));

        return [
            'castingProject' => $castingProject,
            'shootingDate' => $shootingDate,
            'namaClient' => $castingProject->tampilkan_nama_client ? $castingProject->namaClient() : null,
            'mapsUrl' => $shootingDate->lokasi ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($shootingDate->lokasi) : null,
            'peran' => $peran,
            'panggilan' => $shootingDate->panggilan ?? [],
            'korlap' => $korlap,
            'warnings' => $warnings,
        ];
    }

    /** CS.2.1: teks polos siap tempel; TIDAK boleh mengandung penanda TTD (itu cuma pratinjau Admin). */
    private function teksWa(array $data): string
    {
        $p = $data['castingProject'];
        $d = $data['shootingDate'];
        $jam = $d->jam_mulai ? substr($d->jam_mulai, 0, 5).($d->jam_selesai ? '-'.substr($d->jam_selesai, 0, 5) : '') : '(belum diisi)';

        $baris = [
            "*CALLSHEET - {$p->nama_produksi}* ({$p->kode_proyek})",
        ];
        if ($data['namaClient']) {
            $baris[] = "Client: {$data['namaClient']}";
        }
        $baris[] = $d->tanggal->translatedFormat('l, d F Y');
        $baris[] = 'Lokasi: '.($d->lokasi ?: '(belum diisi)').($data['mapsUrl'] ? " - {$data['mapsUrl']}" : '');
        $baris[] = "Jam: {$jam} WIB";
        $baris[] = '';

        foreach ($data['peran'] as $peran) {
            $baris[] = "*{$peran['nama']}*";
            $baris[] = 'Jam kumpul: '.($peran['jam_kumpul'] ?: '(belum diisi)');
            if ($peran['kostum_scene']) {
                $baris[] = "Kostum/Scene: {$peran['kostum_scene']}";
            }
            $baris[] = "Kontinuitas: {$peran['kontinuitas']}";
            foreach ($peran['talents'] as $t) {
                $baris[] = "{$t['no']}. {$t['nama']}".($t['catatan'] ? " ({$t['catatan']})" : '');
            }
            $baris[] = '';
        }

        if ($data['panggilan']) {
            $baris[] = '*Panggilan:*';
            foreach ($data['panggilan'] as $pg) {
                $baris[] = "- {$pg['nama']} ({$pg['jam']})";
            }
            $baris[] = '';
        }

        if ($data['korlap']) {
            $baris[] = '*Korlap:*';
            foreach ($data['korlap'] as $k) {
                $baris[] = $k['nama'].($k['wa'] ? " - wa.me/{$k['wa']}" : '');
            }
            $baris[] = '';
        }

        if ($d->catatan) {
            $baris[] = "Catatan: {$d->catatan}";
        }

        return trim(implode("\n", $baris));
    }
}
