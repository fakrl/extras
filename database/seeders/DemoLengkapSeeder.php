<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\CastingProject;
use App\Models\CastingProjectClass;
use App\Models\EventShootingDate;
use App\Models\ExtrasCategory;
use App\Models\Payment;
use App\Models\ProjectApplication;
use App\Models\User;
use App\Notifications\InAppNotification;
use App\Services\KeuanganService;
use App\Services\PdfGeneratorService;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SPEC BC: data demo semua alur, sumber kebenaran docs/AKUN-DEMO.md.
 * Semua data dibuat langsung lewat model, tidak ada mail/WA/job yang dipanggil.
 */
class DemoLengkapSeeder extends Seeder
{
    use WithoutModelEvents;

    private array $u = [];

    private string $password;

    public function run(PdfGeneratorService $pdf, KeuanganService $keuangan): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('DemoLengkapSeeder tidak boleh dijalankan di production.');
        }

        $this->password = Hash::make('password');

        Model::unguarded(function () use ($pdf, $keuangan) {
            $this->staf();
            $this->extras();
            $this->proyek($pdf, $keuangan);
            $this->notifikasiUmum();
        });

        // BE.1: lengkapi payment/invoice yang nggak diisi manual, sama seperti migration backfill.
        ProjectApplication::whereIn('status_partisipasi', ProjectApplication::STATUS_LOLOS_KE_ATAS)->get()
            ->each->siapkanKontrakDanPembayaran(kirimNotifikasi: false);
    }

    private function staf(): void
    {
        foreach ([
            ['super_admin', 'Fakhrul Mukhlisin', 'fakrul', 'fahrulmukhlisin13@gmail.com', ['is_protected' => true]],
            ['super_admin', 'Jessica Tanubrata', 'owner_jbtb', 'owner@jbtb.test'],
            ['admin', 'Rina Kartika', 'admin_rina', 'rina@jbtb.test', [], 1500000],
            ['admin', 'Yoga Pratama', 'admin_yoga', 'yoga@jbtb.test', [], 1500000],
            ['admin', 'Lestari Wulan', 'admin_lestari', 'lestari@jbtb.test', [], 1000000],
            ['korlap', 'Bambang Susilo', 'korlap_bambang', 'bambang@jbtb.test', [], 750000],
            ['korlap', 'Dedi Firmansyah', 'korlap_dedi', 'dedi@jbtb.test', [], 750000],
            ['admin', 'Hendra Wijaya', 'admin_hendra', 'hendra@jbtb.test', ['status' => 'nonaktif'], 1000000],
            ['client', 'Andini Prameswari', 'client_andini', 'andini@layarsenja.test', ['nama_perusahaan' => 'PT Layar Senja Films']],
            ['client', 'Rudy Hartono', 'client_rudy', 'rudy@kampusbiru.test', ['nama_perusahaan' => 'Kampus Biru Pictures']],
            ['client', 'Maya Salsabila', 'client_maya', 'maya@nadaria.test', ['nama_perusahaan' => 'Nadaria Music']],
        ] as $s) {
            [$role, $nama, $username, $email] = $s;
            $user = $this->akun($role, $nama, $username, $email, now()->subDays(120), $s[4] ?? []);
            if (isset($s[5])) {
                $user->update(['honor_nominal' => $s[5]]);
            }
        }
    }

    private function extras(): void
    {
        $tag = ExtrasCategory::pluck('id', 'nama');
        $baju = ['pria' => 'L', 'wanita' => 'M'];

        // username, nama, email, gender, usia, tinggi, tags, grade, daftar (hari lalu), bahasa, pengalaman, rate card
        foreach ([
            ['dimas_rk', 'Dimas Rizky Kurniawan', 'dimas', 'pria', 23, 172, ['Dewasa muda', 'Mahasiswa', 'Naik motor', 'Sunda'], 'A', 90, 'Indonesia, Sunda', 'Aktif teater kampus. Pernah jadi figuran mahasiswa di 3 FTV.', 250000],
            ['sari_mei', 'Sari Meilani', 'sari', 'wanita', 21, 160, ['Dewasa muda', 'Berhijab', 'Mahasiswa'], 'B', 90, 'Indonesia', 'Model katalog hijab untuk brand lokal.', 225000],
            ['bagas22', 'Bagas Pratama', 'bagas', 'pria', 22, 175, ['Dewasa muda', 'Atlet', 'Naik motor', 'Jawa'], 'A', 90, 'Indonesia, Jawa', 'Atlet futsal kampus. Pernah jadi extras iklan minuman olahraga.', 250000],
            ['nadia_pu', 'Nadia Putri', 'nadia', 'wanita', 24, 158, ['Dewasa muda', 'Rambut panjang', 'Menari'], 'C', 60, 'Indonesia', 'Penari tradisional sanggar. Baru mulai casting figuran.', 200000],
            ['rehan_x', 'Rehan Saputra', 'rehan', 'pria', 19, 169, ['Remaja', 'Mahasiswa', 'Naik motor'], null, 60, 'Indonesia', 'Baru pertama ikut casting, aktif konten TikTok.', 200000],
            ['citra_ay', 'Citra Ayu', 'citra', 'wanita', 25, 163, ['Dewasa muda', 'Berhijab', 'Menari'], 'B', 60, 'Indonesia', 'Pernah jadi extras video klip dan iklan ramadan.', 200000],
            ['pak_harto', 'Suharto Wibowo', 'harto', 'pria', 58, 168, ['Orang Tua', 'Jawa', 'Nyetir mobil'], 'A', 90, 'Indonesia, Jawa', 'Pensiunan guru. Sering jadi bapak-bapak warga di film layar lebar.', 300000],
            ['bu_ningsih', 'Ningsih Rahayu', 'ningsih', 'wanita', 52, 155, ['Orang Tua', 'Berhijab', 'Jawa'], null, 90, 'Indonesia, Jawa', 'Ibu rumah tangga, pernah jadi pedagang pasar di sinetron.', 200000],
            ['opa_liem', 'Liem Hok Tjoan', 'liem', 'pria', 67, 165, ['Lansia', 'Chinese/Tionghoa'], null, 20, 'Indonesia, Mandarin', 'Pemilik toko kelontong, baru daftar karena diajak cucu.', 300000],
            ['kevin_t', 'Kevin Tanoto', 'kevin', 'pria', 27, 178, ['Dewasa', 'Chinese/Tionghoa', 'Pekerja kantoran'], 'B', 60, 'Indonesia, Inggris', 'Karyawan swasta. Pernah jadi extras iklan bank dan properti.', 250000],
            ['aisyah_f', 'Aisyah Fauziah', 'aisyah', 'wanita', 30, 162, ['Dewasa', 'Berhijab', 'Pekerja kantoran', 'Timur Tengah'], null, 60, 'Indonesia, Arab', 'Guru les privat. Pernah jadi extras iklan ramadan.', 250000],
            ['yohanes_m', 'Yohanes Mote', 'yohanes', 'pria', 28, 176, ['Dewasa', 'Indonesia Timur', 'Atlet', 'Berenang'], 'A', 60, 'Indonesia', 'Pelatih renang. Pernah jadi stunt ringan di film aksi.', 300000],
            ['clara_b', 'Clara Bennett', 'clara', 'wanita', 26, 170, ['Dewasa', 'Kaukasia/Bule', 'Berenang'], null, 60, 'Inggris, Indonesia', 'Guru bahasa Inggris, tinggal di Jakarta 5 tahun.', 350000],
            ['fajar_n', 'Fajar Nugroho', 'fajar', 'pria', 33, 171, ['Dewasa', 'Bertato', 'Naik motor'], null, 60, 'Indonesia', 'Ojek online. Cocok peran preman/pengendara.', 200000],
            ['wulan_s', 'Wulan Sari', 'wulan', 'wanita', 20, 157, ['Remaja', 'Mahasiswa', 'Sunda'], null, 45, 'Indonesia, Sunda', 'Mahasiswi semester 3, anggota paduan suara.', 200000],
            ['tono_g', 'Tono Gunawan', 'tono', 'pria', 45, 170, ['Dewasa', 'Pekerja kantoran', 'Nyetir mobil'], 'B', 90, 'Indonesia', 'Sopir kantor. Sering jadi figuran bapak kantoran.', 200000],
            ['melati_k', 'Melati Kusuma', 'melati', 'wanita', 35, 160, ['Dewasa', 'Melayu', 'Menari'], null, 60, 'Indonesia, Melayu', 'Pelatih tari zapin. Pernah tampil di acara TV daerah.', 250000],
            ['arga_p', 'Arga Permana', 'arga', 'pria', 24, 180, ['Dewasa muda', 'Atlet', 'Berenang'], null, 60, 'Indonesia', 'Personal trainer gym. Pernah jadi extras iklan suplemen.', 250000],
            ['intan_r', 'Intan Rosalina', 'intan', 'wanita', 22, 165, ['Dewasa muda', 'Rambut panjang', 'Mahasiswa'], null, 60, 'Indonesia', 'Mahasiswi komunikasi, aktif jadi MC acara kampus.', 200000],
            ['joko_s', 'Joko Santoso', 'joko', 'pria', 40, 167, ['Dewasa', 'Jawa', 'Naik motor'], null, 90, 'Indonesia, Jawa', 'Pedagang bakso keliling. Pernah jadi warga di 2 film.', 200000],
            ['putri_a', 'Putri Anggraini', 'putri', 'wanita', 23, 161, ['Dewasa muda', 'Berhijab'], null, 15, 'Indonesia', 'Karyawan toko baju.', 200000],
            ['lama_dihapus', 'Rahmat Hidayat', 'rahmat', 'pria', 29, 170, ['Dewasa'], null, 50, 'Indonesia', 'Pernah ikut 1 produksi FTV.', 200000],
        ] as $i => [$username, $nama, $mail, $g, $usia, $tinggi, $tags, $grade, $daftar, $bahasa, $pengalaman, $rate]) {
            $no = $i + 1;
            $user = $this->akun('extras', $nama, $username, "{$mail}@extras.test", now()->subDays($daftar), ['nomor_wa' => sprintf('08120000%04d', $no)]);
            $profile = $user->extrasProfile()->create([
                'nik' => sprintf('3276%012d', $no),
                'nama_asli' => $nama,
                'usia' => $usia,
                'gender' => $g,
                'tinggi_badan' => $tinggi,
                'ukuran_baju' => $baju[$g],
                'berat_badan' => $tinggi - ($g === 'pria' ? 105 : 110),
                'riwayat_pengalaman' => collect(explode('. ', rtrim($pengalaman, '.')))->map(fn ($judul, $k) => [
                    'judul' => $judul, 'keterangan' => null, 'tahun' => now()->year - $k - $no % 3,
                ])->all(),
                'bahasa' => $bahasa,
                'rate_card' => $rate,
                'rekening' => sprintf('BCA 12345%05d a.n. %s', $no, $nama),
                'grade_saat_ini' => $grade,
                'grade_diberikan_at' => $grade ? now()->subDays(30) : null,
                'created_at' => now()->subDays($daftar),
            ]);
            $profile->categories()->sync($tag->only([...$tags, $usia > 50 ? 'Sawo matang' : 'Kuning langsat'])->values());
        }

        $this->u['dimas_rk']->extrasProfile->update(['apresiasi' => true, 'apresiasi_catatan' => 'Disukai Client, selalu on-time & gampang diarahkan.']);
        $this->u['joko_s']->extrasProfile->forceFill(['status' => 'melanggar'])->save();
        $this->u['putri_a']->update(['status' => 'nonaktif']);
        $this->u['lama_dihapus']->delete();

        // BH.2: 8 Extras izin + tampil di beranda (foto placeholder SVG), arga_p izin tapi belum di-ACC Admin.
        foreach (['dimas_rk', 'sari_mei', 'bagas22', 'citra_ay', 'pak_harto', 'kevin_t', 'yohanes_m', 'clara_b', 'arga_p'] as $i => $un) {
            $profile = $this->u[$un]->extrasProfile;
            $profile->forceFill([
                'foto_profil_path' => $this->fotoDemo($un, $i),
                'izin_tampil_publik' => true,
                'tampil_di_beranda' => $un !== 'arga_p',
                'tampil_di_beranda_at' => $un !== 'arga_p' ? now()->subDays($i) : null,
            ])->save();
            $profile->generateShareToken();
        }

        $ghost = $this->akun('extras', 'ghost01', 'ghost01', 'ghost01@extras.test', now()->subDays(40));
        $ghost->extrasProfile()->create(['created_at' => now()->subDays(40)]);

        $rizka = $this->akun('extras', 'Rizka Amelia', 'baru_daftar', 'rizka@extras.test', now()->subDays(2));
        $rizka->extrasProfile()->create(['nama_asli' => 'Rizka Amelia', 'usia' => 21, 'gender' => 'wanita', 'tinggi_badan' => 159])
            ->categories()->sync($tag->only(['Dewasa muda'])->values());
    }

    private function proyek(PdfGeneratorService $pdf, KeuanganService $keuangan): void
    {
        [$fakrul, $rina, $yoga, $bambang, $dedi] = [$this->u['fakrul'], $this->u['admin_rina'], $this->u['admin_yoga'], $this->u['korlap_bambang'], $this->u['korlap_dedi']];
        [$andini, $rudy, $maya] = [$this->u['client_andini'], $this->u['client_rudy'], $this->u['client_maya']];

        // P1: selesai
        $p1 = $this->project('Film "Rumah di Ujung Senja"', $rina, -45, ['deadline' => today()->subDays(35), 'kuota' => 7, 'status' => 'ditutup', 'client_id' => $andini->id]);
        $tgl1 = $this->jadwal($p1, [-30, -29, -28], 'Desa Cibodas, Lembang');
        $warga = $this->kelas($p1, 'Warga kampung', 4, 300000, ['Orang Tua', 'Jawa']);
        $mhsKos = $this->kelas($p1, 'Mahasiswa kos', 3, 350000, ['Dewasa muda', 'Mahasiswa']);

        foreach ([['pak_harto', $warga, 200000], ['bu_ningsih', $warga, 200000], ['tono_g', $warga, 200000], ['joko_s', $warga, 200000], ['dimas_rk', $mhsKos, 250000], ['bagas22', $mhsKos, 250000], ['sari_mei', $mhsKos, 225000]] as [$un, $kelas, $fee]) {
            $a = $this->daftar($p1, $kelas, $un, 'selesai_produksi', -40);
            $this->deal($a, $fee, -38);
            $this->greenlight($a, $andini, -36);
            if ($un === 'joko_s') {
                $a->update(['status_partisipasi' => 'dibatalkan']);
                $a->cancellations()->create(['dibatalkan_oleh' => 'extras', 'alasan' => 'Ada acara keluarga mendadak.', 'is_mendadak' => true, 'created_at' => now()->subDays(31)]);

                continue;
            }
            $this->kontrak($pdf, $a, $rina, true, -33);
            foreach ($tgl1 as $t) {
                $a->attendances()->create(['event_shooting_date_id' => $t->id, 'status' => 'hadir', 'dicatat_oleh' => $bambang->id, 'status_validasi' => 'tervalidasi', 'divalidasi_oleh' => $bambang->id, 'divalidasi_at' => $t->tanggal->copy()->setTime(7, 30)]);
            }
            $status = match ($un) {
                'tono_g' => 'ditransfer',
                'sari_mei' => 'disengketakan',
                default => 'dikonfirmasi_diterima',
            };
            $pay = $this->bayar($a, $status, -25, $rina);
            if ($un === 'dimas_rk') {
                $pay->addons()->create(['label' => 'Transport', 'nominal' => 50000, 'created_by' => $rina->id]);
            }
        }
        $this->app($p1, 'pak_harto')->tambahCatatan($bambang, 'catatan', 'Datang paling pagi tiap hari, bantu arahkan extras lain.');
        $this->app($p1, 'dimas_rk')->tambahCatatan($bambang, 'catatan', 'Improvisasi bagus di scene warung kopi.');
        $this->app($p1, 'joko_s')->tambahCatatan($bambang, 'sanksi', 'Batal H-1 tanpa pengganti, pembatalan mendadak ke-3.');
        $this->log($this->u['sari_mei'], 'DISPUTE_PAYMENT', "Extras Sari Meilani menandai pembayaran sebagai sengketa untuk proyek '{$p1->nama_produksi}'", $this->app($p1, 'sari_mei'), -24);

        $inv1 = $p1->invoices()->create([
            'ttd_admin_signature_path' => $this->png("invoices/signatures/{$p1->id}-admin-demo.png"),
            'ttd_client_signature_path' => $this->png("invoices/signatures/{$p1->id}-client-demo.png"),
            'created_at' => now()->subDays(27),
        ]);
        $p1->load('classes', 'applications.extras');
        $inv1->update(['pdf_path' => $pdf->generate('invoices.pdf-template', ['castingProject' => $p1, 'invoice' => $inv1, 'rincian' => $keuangan->rincianInvoice($p1)], "invoices/pdf/{$p1->id}.pdf")]);
        $inv1->update(['nominal' => $keuangan->nilaiInvoice($p1), 'status_bayar' => 'lunas', 'dibayar_at' => now()->subDays(22)]);
        $this->biaya($p1, $rina, [['Konsumsi 3 hari shooting', 350000, -29], ['Sewa elf antar-jemput', 250000, -30]]);
        $this->log($rina, 'SIGN_INVOICE', "Admin Rina Kartika menandatangani invoice untuk proyek '{$p1->nama_produksi}'", $p1, -27);
        $this->log($andini, 'SIGN_INVOICE', "Client Andini Prameswari menandatangani invoice untuk proyek '{$p1->nama_produksi}'", $p1, -26);

        foreach ([$rina, $bambang] as $staf) {
            $asg = $p1->adminAssignments()->create(['user_id' => $staf->id, 'assigned_by' => $fakrul->id, 'created_at' => now()->subDays(45)]);
            $payroll = $asg->tandaiSelesai();
            $asg->update(['completed_at' => now()->subDays(27)]);
            $asg->load('user', 'castingProject');
            $payroll->tandaiSlipDibuat($pdf->generate('payrolls.pdf-template', ['assignment' => $asg, 'payroll' => $payroll], "payrolls/pdf/{$payroll->id}.pdf"));
        }
        $p1->adminAssignments()->where('user_id', $rina->id)->first()->payroll->update(['status_bayar' => 'sudah', 'dibayar_at' => now()->subDays(20)]);

        // P2: berjalan, satu Extras di tiap tahap
        $p2 = $this->project('Iklan "Minuman Segar"', $rina, -7, [
            'deadline' => today()->addDays(3), 'kuota' => 15, 'client_id' => $andini->id,
            'brief_catatan' => 'Iklan TV 30 detik minuman isotonik, suasana kampus & kantor.', 'link_grup' => 'https://chat.whatsapp.com/demo-minuman-segar',
        ]);
        $this->jadwal($p2, [5, 6], 'Kampus UI Depok');
        $mhs = $this->kelas($p2, 'Mahasiswa kampus', 5, 300000, ['Dewasa muda', 'Mahasiswa', 'Naik motor']);
        $kantor = $this->kelas($p2, 'Pekerja kantoran', 3, 350000, ['Dewasa', 'Pekerja kantoran']);
        foreach ([$rina, $dedi] as $staf) {
            $p2->adminAssignments()->create(['user_id' => $staf->id, 'assigned_by' => $fakrul->id]);
        }
        $this->log($andini, 'SUBMIT_PROJECT_REQUEST', "Client Andini Prameswari mengajukan proyek '{$p2->nama_produksi}'", $p2, -8);
        $this->log($fakrul, 'APPROVE_PROJECT_REQUEST', "Super Admin menyetujui (ACC) permintaan proyek '{$p2->nama_produksi}' dari Client", $p2, -7);

        $this->daftar($p2, $mhs, 'wulan_s', 'diajukan', 0);
        $this->daftar($p2, $mhs, 'intan_r', 'direview_admin', -2)->update(['grade' => 'B']);
        $rehan = $this->daftar($p2, $mhs, 'rehan_x', 'nego_fee', -3);
        $rehan->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 150000, 'aksi' => 'tawar', 'created_at' => now()->subDays(2)]);
        $rehan->feeNegotiations()->create(['round' => 2, 'diajukan_oleh' => 'extras', 'nominal' => 200000, 'aksi' => 'counter', 'catatan' => 'Lokasi jauh dari rumah, mohon dinaikkan.', 'created_at' => now()->subDay()]);
        $arga = $this->daftar($p2, $mhs, 'arga_p', 'nego_fee', -3);
        $arga->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 175000, 'aksi' => 'tawar', 'created_at' => now()->subDay()]);
        $this->deal($this->daftar($p2, $mhs, 'citra_ay', 'deal', -4), 175000, -2);
        $this->log($this->u['citra_ay'], 'DEAL_FEE', "Extras Citra Ayu menyetujui fee Rp 175.000 untuk proyek '{$p2->nama_produksi}'", $this->app($p2, 'citra_ay'), -2);
        $this->deal($this->daftar($p2, $mhs, 'dimas_rk', 'diajukan_ke_client', -5), 250000, -3);
        $this->deal($this->daftar($p2, $kantor, 'kevin_t', 'diajukan_ke_client', -5), 250000, -3);
        $bagas = $this->daftar($p2, $mhs, 'bagas22', 'lolos', -6);
        $this->deal($bagas, 250000, -5);
        $this->greenlight($bagas, $andini, -4);
        $this->kontrak($pdf, $bagas, $rina, false, -1);
        $aisyah = $this->daftar($p2, $kantor, 'aisyah_f', 'kontrak_ditandatangani', -6);
        $this->deal($aisyah, 250000, -5);
        $this->greenlight($aisyah, $andini, -4);
        $this->kontrak($pdf, $aisyah, $rina, true, -2);
        $this->bayar($aisyah, 'belum_dibayar', -2, $rina);
        $this->daftar($p2, $mhs, 'nadia_pu', 'ditolak', -5)->update(['alasan_tolak' => 'Tinggi tidak sesuai kriteria.']);
        $fajar = $this->daftar($p2, $kantor, 'fajar_n', 'dibatalkan', -6);
        $this->deal($fajar, 200000, -5);
        $fajar->cancellations()->create(['dibatalkan_oleh' => 'extras', 'alasan' => 'Bentrok jadwal kerja.', 'is_mendadak' => false, 'created_at' => now()->subDays(3)]);
        $p2->invoices()->create([]);
        $this->biaya($p2, $rina, [['DP konsumsi', 300000, -1]]);

        // P3: shooting hari ini & besok
        $p3 = $this->project('Series "Kampus Biru" eps 1-2', $yoga, -20, ['deadline' => today()->subDays(5), 'kuota' => 8, 'status' => 'ditutup', 'link_grup' => 'https://chat.whatsapp.com/demo-kampus-biru', 'client_id' => $rudy->id]);
        [$hariIni] = $this->jadwal($p3, [0, 1], 'Kampus Universitas Pamulang');
        $mhs3 = $this->kelas($p3, 'Mahasiswa', 6, 300000, ['Mahasiswa']);
        $dosen = $this->kelas($p3, 'Dosen', 2, 400000, ['Orang Tua']);
        foreach ([$yoga, $bambang] as $staf) {
            $p3->adminAssignments()->create(['user_id' => $staf->id, 'assigned_by' => $fakrul->id, 'created_at' => now()->subDays(20)]);
        }
        foreach (['yohanes_m', 'clara_b', 'melati_k', 'intan_r', 'pak_harto', 'wulan_s'] as $un) {
            $a = $this->daftar($p3, $un === 'pak_harto' ? $dosen : $mhs3, $un, 'kontrak_ditandatangani', -15);
            $this->deal($a, $un === 'pak_harto' ? 300000 : 200000, -12);
            $this->greenlight($a, $rudy, -10);
            $this->kontrak($pdf, $a, $yoga, true, -7);
            $this->bayar($a, $un === 'clara_b' ? 'ditransfer' : 'belum_dibayar', 0, $yoga);

            match ($un) {
                'yohanes_m', 'clara_b' => $this->selfie($a, $hariIni, $bambang),
                'melati_k', 'intan_r' => $this->selfie($a, $hariIni),
                'pak_harto' => $a->attendances()->create(['event_shooting_date_id' => $hariIni->id, 'status' => 'tidak_hadir', 'dicatat_oleh' => $bambang->id, 'catatan' => 'Izin sakit, dikabari via telepon jam 06.00.', 'status_validasi' => 'tervalidasi', 'divalidasi_oleh' => $bambang->id, 'divalidasi_at' => now()]),
                default => null,
            };
        }

        $this->biaya($p3, $yoga, [['Transport korlap', 150000, 0]]);

        // P4: pengajuan Client, menunggu ACC
        $p4 = CastingProject::create([
            'nama_produksi' => 'Video Klip "Nadaria"', 'share_token' => Str::random(32),
            'deadline' => today()->addDays(14), 'kuota' => 20, 'brief_catatan' => 'Video klip single baru, butuh 20 extras penonton konser umur 18-30.',
            'client_id' => $maya->id, 'client_request_status' => 'menunggu_acc', 'status' => 'ditutup',
            'created_at' => now()->subDay(),
        ]);
        $this->log($maya, 'SUBMIT_PROJECT_REQUEST', "Client Maya Salsabila mengajukan proyek '{$p4->nama_produksi}'", $p4, -1);

        // P5: pengajuan Client, ditolak
        $p5 = CastingProject::create([
            'nama_produksi' => '"Kampus Biru" season 2', 'share_token' => Str::random(32),
            'deadline' => today()->addDays(20), 'kuota' => 10, 'brief_catatan' => 'Lanjutan season 1, pemain extras yang sama kalau bisa.',
            'client_id' => $rudy->id, 'client_request_status' => 'ditolak', 'status' => 'ditutup',
            'alasan_tolak' => 'Jadwal bentrok produksi lain, ajukan ulang bulan depan.', 'created_at' => now()->subDays(10),
        ]);
        $this->log($rudy, 'SUBMIT_PROJECT_REQUEST', "Client Rudy Hartono mengajukan proyek '{$p5->nama_produksi}'", $p5, -10);
        $this->log($fakrul, 'REJECT_PROJECT_REQUEST', "Super Admin menolak permintaan proyek '{$p5->nama_produksi}' dari Client", $p5, -9);

        // P6: lowongan urgent kosong
        $p6 = $this->project('Iklan "Bank Digital"', $yoga, -1, ['deadline' => today()->addDays(7), 'kuota' => 10, 'is_urgent' => true]);
        $this->jadwal($p6, [10], 'SCBD, Jakarta Selatan');
        $this->kelas($p6, 'Nasabah muda', 10, 450000, ['Dewasa', 'Pekerja kantoran']);
        $p6->adminAssignments()->create(['user_id' => $yoga->id, 'assigned_by' => $fakrul->id]);

        // BH.3: portofolio beranda, P1 + 2 arsip lama (satu izinkan nama client)
        $p1->update(['tampil_portofolio' => true, 'portofolio_jenis' => 'Film layar lebar', 'portofolio_tahun' => today()->subDays(30)->year]);
        foreach ([['Iklan TV "Kopi Pagi Nusantara"', 'PT Kopi Pagi Nusantara', 'Iklan TV', -200, true], ['FTV "Cinta di Pasar Minggu"', 'Sinar Rumah Produksi', 'FTV', -320, false]] as [$nama, $ph, $jenis, $hari, $client]) {
            $klien = $this->akun('client', $ph, 'client_'.Str::slug($ph, '_'), Str::slug($ph).'@arsip.test', now()->addDays($hari - 30), ['nama_perusahaan' => $ph, 'status' => 'nonaktif']);
            $arsip = $this->project($nama, $rina, $hari - 20, [
                'client_id' => $klien->id, 'status' => 'ditutup', 'deadline' => today()->addDays($hari - 5), 'kuota' => 10, 'tampil_portofolio' => true,
                'portofolio_jenis' => $jenis, 'portofolio_tahun' => today()->addDays($hari)->year, 'tampilkan_nama_client' => $client,
            ]);
            $this->jadwal($arsip, [$hari], 'Jakarta');
            $this->notif($klien, 'Proyek Selesai', "Proyek '{$nama}' selesai. Terima kasih sudah bekerja sama.", null, true, now()->addDays($hari + 1));
        }

        $this->notifikasiEvent($p1, $p2, $p3, $p4, $p5, $p6);
    }

    private function notifikasiEvent(CastingProject $p1, CastingProject $p2, CastingProject $p3, CastingProject $p4, CastingProject $p5, CastingProject $p6): void
    {
        $bayar = fn (CastingProject $p, string $un) => route('payments.show', $this->app($p, $un));
        $kontrak = fn (CastingProject $p, string $un) => route('contracts.show', $this->app($p, $un));
        $nego = fn (string $un) => route('extras.negotiations.show', $this->app($p2, $un));

        foreach ([
            ['fakrul', 'Pengajuan Proyek Baru', "Client Maya mengajukan proyek '{$p4->nama_produksi}', menunggu ACC.", route('super-admin.dashboard'), false],
            ['fakrul', 'Pembayaran Disengketakan', "Sari Meilani menyengketakan honor proyek '{$p1->nama_produksi}'.", $bayar($p1, 'sari_mei'), false],
            ['owner_jbtb', 'Pengajuan Proyek Baru', "Client Maya mengajukan proyek '{$p4->nama_produksi}', menunggu ACC.", route('super-admin.dashboard'), false],
            ['admin_rina', 'Counter Fee dari Extras', "rehan_x mengajukan counter Rp 200.000 di proyek '{$p2->nama_produksi}'. Giliran kamu.", route('admin.negotiations.show', $this->app($p2, 'rehan_x')), false],
            ['admin_rina', 'Pembayaran Disengketakan', "Sari Meilani: \"nominal kurang 1 hari\" di proyek '{$p1->nama_produksi}'.", $bayar($p1, 'sari_mei'), false],
            ['admin_rina', 'Honor Dibayar', "Honor kamu untuk proyek '{$p1->nama_produksi}' sudah dibayar.", null, true],
            ['admin_yoga', 'Selfie Menunggu Validasi', "2 selfie absensi '{$p3->nama_produksi}' hari ini menunggu validasi Korlap.", route('admin.attendance.index', ['project' => $p3->id]), false],
            ['admin_yoga', 'Lowongan Dibuka', "Lowongan '{$p6->nama_produksi}' sudah tayang, belum ada pendaftar.", route('admin.projects.applicants', $p6), true],
            ['admin_lestari', 'Akun Admin Dibuat', 'Akun admin kamu sudah aktif. Tunggu penugasan proyek dari Super Admin.', null, false],
            ['admin_hendra', 'Akun Dinonaktifkan', 'Akun kamu dinonaktifkan Super Admin.', null, false],
            ['korlap_bambang', 'Selfie Menunggu Validasi', "melati_k & intan_r mengirim selfie absensi '{$p3->nama_produksi}'.", route('admin.attendance.index', ['project' => $p3->id]), false],
            ['korlap_bambang', 'Honor Tercatat', "Slip honor proyek '{$p1->nama_produksi}' sudah dibuat, menunggu pembayaran.", null, true],
            ['korlap_dedi', 'Penugasan Proyek', "Kamu ditugaskan sebagai Korlap di proyek '{$p2->nama_produksi}'.", null, false],
            ['client_andini', 'Kandidat Menunggu Greenlight', "2 kandidat proyek '{$p2->nama_produksi}' menunggu keputusan kamu.", route('client.reviews.show', $p2), false],
            ['client_andini', 'Invoice Siap Diunduh', "Invoice proyek '{$p1->nama_produksi}' sudah ditandatangani kedua pihak.", route('invoices.show', $p1), true],
            ['client_andini', 'Pengajuan Proyek Disetujui', "Pengajuan proyek '{$p2->nama_produksi}' disetujui tim JBTB.", route('client.dashboard'), true],
            ['client_rudy', 'Pengajuan Proyek Ditolak', "Pengajuan proyek '{$p5->nama_produksi}' ditolak. Alasan: {$p5->alasan_tolak}", route('client.dashboard'), false],
            ['client_rudy', 'Shooting Hari Ini', "Shooting '{$p3->nama_produksi}' berlangsung hari ini, pantau foto kehadiran.", route('client.jadwal.show', $p3), false],
            ['client_maya', 'Pengajuan Terkirim', "Pengajuan proyek '{$p4->nama_produksi}' terkirim, menunggu ACC Super Admin.", route('client.dashboard'), true],
            ['dimas_rk', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'dimas_rk'), true],
            ['dimas_rk', 'Diajukan ke Client', "Kamu diajukan ke Client untuk proyek '{$p2->nama_produksi}'.", route('extras.dashboard'), false],
            ['sari_mei', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'sari_mei'), true],
            ['sari_mei', 'Sengketa Dicatat', 'Laporan sengketa kamu sudah diterima Admin.', $bayar($p1, 'sari_mei'), false],
            ['bagas22', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'bagas22'), true],
            ['bagas22', 'Kontrak Siap Ditandatangani', "Kontrak proyek '{$p2->nama_produksi}' sudah ditandatangani Admin. Giliran kamu.", $kontrak($p2, 'bagas22'), false],
            ['nadia_pu', 'Hasil Seleksi', "Mohon maaf, kamu belum lolos seleksi proyek '{$p2->nama_produksi}' kali ini.", route('extras.dashboard'), false],
            ['rehan_x', 'Penawaran Fee', "Admin menawarkan Rp 150.000 untuk proyek '{$p2->nama_produksi}'.", $nego('rehan_x'), true],
            ['arga_p', 'Penawaran Fee', "Admin menawarkan Rp 175.000 untuk proyek '{$p2->nama_produksi}'. Terima atau ajukan counter.", $nego('arga_p'), false],
            ['citra_ay', 'Fee Deal', "Fee Rp 175.000 untuk proyek '{$p2->nama_produksi}' sudah deal.", $nego('citra_ay'), false],
            ['pak_harto', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'pak_harto'), true],
            ['pak_harto', 'Kontrak Ditandatangani', "Kontrak proyek '{$p3->nama_produksi}' sudah lengkap.", $kontrak($p3, 'pak_harto'), true],
            ['bu_ningsih', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'bu_ningsih'), true],
            ['kevin_t', 'Diajukan ke Client', "Kamu diajukan ke Client untuk proyek '{$p2->nama_produksi}'.", route('extras.dashboard'), false],
            ['aisyah_f', 'Kontrak Ditandatangani', "Kontrak proyek '{$p2->nama_produksi}' sudah lengkap.", $kontrak($p2, 'aisyah_f'), false],
            ['yohanes_m', 'Absensi Tervalidasi', "Kehadiran kamu di '{$p3->nama_produksi}' hari ini sudah divalidasi Korlap.", route('extras.dashboard'), false],
            ['clara_b', 'Pembayaran Ditransfer', "Honor proyek '{$p3->nama_produksi}' sudah ditransfer. Silakan konfirmasi.", $bayar($p3, 'clara_b'), false],
            ['fajar_n', 'Pembatalan Tercatat', "Pembatalan kamu untuk proyek '{$p2->nama_produksi}' sudah tercatat.", route('extras.dashboard'), true],
            ['wulan_s', 'Pendaftaran Diterima', "Pendaftaran proyek '{$p2->nama_produksi}' berhasil, Admin akan mereview.", route('extras.dashboard'), true],
            ['wulan_s', 'Shooting Hari Ini', "Shooting '{$p3->nama_produksi}' hari ini. Jangan lupa selfie absensi.", route('extras.dashboard'), false],
            ['tono_g', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer. Silakan konfirmasi.", $bayar($p1, 'tono_g'), false],
            ['melati_k', 'Selfie Terkirim', 'Selfie absensi kamu menunggu validasi Korlap.', route('extras.dashboard'), false],
            ['intan_r', 'Selfie Terkirim', 'Selfie absensi kamu menunggu validasi Korlap.', route('extras.dashboard'), false],
            ['joko_s', 'Status Akun Melanggar', 'Akun kamu berstatus Melanggar karena 3x batal mendadak.', null, false],
            ['putri_a', 'Akun Dinonaktifkan', 'Akun kamu dinonaktifkan Admin.', null, false],
            ['ghost01', 'Lengkapi Profil', 'Profil kamu masih kosong, lengkapi supaya bisa daftar lowongan.', route('extras.profile.edit'), false],
            ['baru_daftar', 'Lengkapi Profil', 'Upload foto profil supaya bisa daftar lowongan.', route('extras.profile.edit'), false],
        ] as [$un, $judul, $pesan, $url, $dibaca]) {
            $this->notif($this->u[$un], $judul, $pesan, $url, $dibaca);
        }

        foreach ($this->u as $un => $user) {
            if ($user->isExtras() && ! in_array($un, ['ghost01', 'baru_daftar'], true)) {
                $this->notif($user, 'Lowongan Butuh Dadakan', "Lowongan '{$p6->nama_produksi}' butuh extras segera. Daftar sebelum {$p6->deadline->format('d M')}.", route('extras.projects.show', $p6), false);
            }
        }
    }

    private function notifikasiUmum(): void
    {
        foreach ($this->u as $user) {
            $this->notif($user, 'Selamat Datang', 'Selamat datang di SIM Casting JBTB.', null, true, $user->created_at);
        }
    }

    private function akun(string $role, string $nama, string $username, string $email, Carbon $at, array $extra = []): User
    {
        return $this->u[$username] = User::create([
            'role' => $role, 'name' => $nama, 'username' => $username, 'email' => $email,
            'password' => $this->password, 'status' => 'aktif', 'email_verified_at' => $at, 'created_at' => $at,
        ] + $extra);
    }

    private function project(string $nama, User $pic, int $dibuat, array $extra = []): CastingProject
    {
        return CastingProject::create($extra + [
            'nama_produksi' => $nama, 'admin_id' => $pic->id, 'share_token' => Str::random(32),
            'status' => 'dibuka', 'client_request_status' => 'disetujui', 'created_at' => now()->addDays($dibuat),
        ]);
    }

    /** @return EventShootingDate[] */
    private function jadwal(CastingProject $p, array $hari, string $lokasi): array
    {
        return array_map(fn ($h) => $p->shootingDates()->create([
            'tanggal' => today()->addDays($h), 'lokasi' => $lokasi, 'jam_mulai' => '07:00', 'jam_selesai' => '17:00',
        ]), $hari);
    }

    private function kelas(CastingProject $p, string $nama, int $kuota, int $budget, array $tags): CastingProjectClass
    {
        $kelas = $p->classes()->create([
            'nama_kelas' => $nama, 'kuota_kelas' => $kuota, 'budget_client' => $budget,
            'jam_callsheet' => '07:00', 'jam_callingan' => '06:00',
        ]);
        $kelas->categories()->sync(ExtrasCategory::whereIn('nama', $tags)->pluck('id'));

        return $kelas;
    }

    private function daftar(CastingProject $p, CastingProjectClass $kelas, string $un, string $status, int $hari): ProjectApplication
    {
        $profile = $this->u[$un]->extrasProfile;

        return $p->applications()->create([
            'extras_id' => $profile->id, 'casting_project_class_id' => $kelas->id, 'status_partisipasi' => $status,
            'grade' => $profile->grade_saat_ini, 'created_at' => now()->addDays($hari),
        ]);
    }

    private function app(CastingProject $p, string $un): ProjectApplication
    {
        return $p->applications()->where('extras_id', $this->u[$un]->extrasProfile->id)->firstOrFail();
    }

    private function deal(ProjectApplication $a, int $fee, int $hari): void
    {
        $a->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => $fee, 'aksi' => 'tawar', 'created_at' => now()->addDays($hari - 1)]);
        $a->feeNegotiations()->create(['round' => 2, 'diajukan_oleh' => 'extras', 'nominal' => $fee, 'aksi' => 'terima', 'created_at' => now()->addDays($hari)]);
        $a->update(['fee_final' => $fee]);
    }

    private function greenlight(ProjectApplication $a, User $client, int $hari): void
    {
        $a->clientReviews()->create(['client_id' => $client->id, 'keputusan' => 'approve', 'created_at' => now()->addDays($hari)]);
        $this->log($client, 'REVIEW_CANDIDATE_LOCK', "Client {$client->name} melakukan lock kandidat {$a->extras->user->username} untuk proyek '{$a->castingProject->nama_produksi}'", $a, $hari);
    }

    private function kontrak(PdfGeneratorService $pdf, ProjectApplication $a, User $admin, bool $lengkap, int $hari): void
    {
        $contract = $a->contract()->create([
            'ttd_admin_signature_path' => $this->png("contracts/signatures/{$a->id}-admin-demo.png"),
            'ttd_extras_signature_path' => $lengkap ? $this->png("contracts/signatures/{$a->id}-extras-demo.png") : null,
            'signed_at' => $lengkap ? now()->addDays($hari) : null,
            'created_at' => now()->addDays($hari),
        ]);
        $a->load('contract', 'extras', 'castingProject');
        $contract->update(['pdf_path' => $pdf->generate('contracts.pdf-template', ['application' => $a], "contracts/pdf/{$a->id}.pdf")]);

        $this->log($admin, 'SIGN_CONTRACT', "Admin {$admin->name} menandatangani kontrak kerja digital untuk proyek '{$a->castingProject->nama_produksi}'", $contract, $hari);
        if ($lengkap) {
            $this->log($a->extras->user, 'SIGN_CONTRACT', "Extras {$a->extras->user->name} menandatangani kontrak kerja digital untuk proyek '{$a->castingProject->nama_produksi}'", $contract, $hari);
        }
    }

    private function bayar(ProjectApplication $a, string $status, int $hari, User $admin): Payment
    {
        $ditransfer = $status !== 'belum_dibayar';
        $payment = $a->payment()->create([
            'status' => $status,
            'bukti_transfer_path' => $ditransfer ? $this->png("payments/bukti-transfer/{$a->id}-demo.png", 'Bukti Transfer Demo') : null,
            'ditransfer_at' => $ditransfer ? now()->addDays($hari) : null,
            'dikonfirmasi_at' => $status === 'dikonfirmasi_diterima' ? now()->addDays($hari + 1) : null,
            'alasan_sengketa' => $status === 'disengketakan' ? 'Nominal kurang 1 hari.' : null,
        ]);
        if ($ditransfer) {
            $this->log($admin, 'UPLOAD_PAYOUT_TRANSFER', "Admin {$admin->name} mengunggah bukti transfer honor untuk {$a->extras->user->name}", $a, $hari);
        }
        if ($status === 'dikonfirmasi_diterima') {
            $this->log($a->extras->user, 'CONFIRM_PAYMENT', "Extras {$a->extras->user->name} mengonfirmasi penerimaan honor (Lunas) untuk proyek '{$a->castingProject->nama_produksi}'", $a, $hari + 1);
        }

        return $payment;
    }

    private function selfie(ProjectApplication $a, EventShootingDate $tgl, ?User $korlap = null): void
    {
        $user = $a->extras->user;
        $absen = $a->attendances()->create([
            'event_shooting_date_id' => $tgl->id, 'status' => 'hadir', 'dicatat_oleh' => $user->id,
            'foto_path' => $this->png("absensi/{$a->id}/demo-selfie.png", 'Selfie Demo'),
            'catatan' => 'Selfie Absensi Extras (Hybrid On-Site)',
            'status_validasi' => $korlap ? 'tervalidasi' : 'menunggu',
            'divalidasi_oleh' => $korlap?->id, 'divalidasi_at' => $korlap ? now() : null,
        ]);
        $this->log($user, 'SUBMIT_SELFIE_ATTENDANCE', "Extras {$user->name} mengirimkan selfie absensi di lokasi shooting untuk proyek {$a->castingProject->nama_produksi}", $absen, 0);
        if ($korlap) {
            $this->log($korlap, 'VALIDATE_ATTENDANCE', "Korlap {$korlap->name} memvalidasi kehadiran extras {$user->name} di lokasi shooting", $absen, 0);
        }
    }

    private function biaya(CastingProject $p, User $oleh, array $rows): void
    {
        foreach ($rows as [$label, $nominal, $hari]) {
            $p->expenses()->create(['label' => $label, 'nominal' => $nominal, 'tanggal' => today()->addDays($hari), 'created_by' => $oleh->id]);
        }
    }

    private function log(User $user, string $action, string $desc, Model $subject, int $hari): void
    {
        ActivityLog::create([
            'user_id' => $user->id, 'role' => $user->role, 'action' => $action, 'description' => $desc,
            'subject_type' => $subject::class, 'subject_id' => $subject->getKey(), 'ip_address' => '127.0.0.1',
            'user_agent' => 'DemoLengkapSeeder', 'created_at' => now()->addDays($hari),
        ]);
    }

    private function notif(User $user, string $judul, string $pesan, ?string $url, bool $dibaca, ?Carbon $at = null): void
    {
        $user->notifications()->create([
            'id' => Str::uuid()->toString(), 'type' => InAppNotification::class,
            'data' => (new InAppNotification($judul, $pesan, $url))->toDatabase($user),
            'read_at' => $dibaca ? now() : null, 'created_at' => $at ?? now(),
        ]);
    }

    private function fotoDemo(string $username, int $i): string
    {
        $h = ($i * 47) % 360;
        $path = "demo/foto/{$username}.svg";
        Storage::disk('local')->put($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 520">'
            ."<defs><linearGradient id=\"g\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"hsl({$h},40%,66%)\"/><stop offset=\"1\" stop-color=\"hsl({$h},35%,30%)\"/></linearGradient></defs>"
            .'<rect width="400" height="520" fill="url(#g)"/>'
            ."<circle cx=\"200\" cy=\"205\" r=\"80\" fill=\"hsl({$h},25%,18%)\" fill-opacity=\".6\"/>"
            ."<path d=\"M50 520c12-125 72-178 150-178s138 53 150 178z\" fill=\"hsl({$h},25%,18%)\" fill-opacity=\".6\"/></svg>");

        return $path;
    }

    private function png(string $path, string $teks = 'TTD Demo'): string
    {
        if (extension_loaded('gd')) {
            $img = imagecreatetruecolor(300, 100);
            imagefill($img, 0, 0, imagecolorallocate($img, 255, 255, 255));
            $hitam = imagecolorallocate($img, 30, 30, 30);
            imageline($img, 20, 75, 280, 75, $hitam);
            imagestring($img, 5, 20, 40, $teks, $hitam);
            ob_start();
            imagepng($img);
            $data = ob_get_clean();
        } else {
            $data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4//8/AAX+Av4N70a4AAAAAElFTkSuQmCC');
        }
        Storage::disk('local')->put($path, $data);

        return $path;
    }
}
