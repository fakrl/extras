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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
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

        if (! app()->runningUnitTests()) {
            $this->bersihkanDatabase();
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
            ['super_admin', 'Jessica Tanubrata', 'owner_jbtb', 'owner@jbtb.test', ['is_protected' => true]],
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

        if ($email = config('demo.sa_email')) {
            $this->akun('super_admin', 'Developer', config('demo.sa_username'), $email, now()->subDays(120), ['is_protected' => true]);
        }

        $sa = $this->u['owner_jbtb'];
        $this->log($sa, 'CREATE_USER', 'Super Admin membuat akun Admin Rina Kartika (@admin_rina)', $this->u['admin_rina'], -120);
        $this->log($sa, 'CREATE_USER', 'Super Admin membuat akun Admin Yoga Pratama (@admin_yoga)', $this->u['admin_yoga'], -120);
        $this->log($sa, 'CREATE_USER', 'Super Admin membuat akun Korlap Bambang Susilo (@korlap_bambang)', $this->u['korlap_bambang'], -120);
        $this->log($sa, 'UPDATE_HONOR', 'Super Admin menetapkan honor Admin Rina Kartika sebesar Rp 1.500.000', $this->u['admin_rina'], -100);
        $this->log($sa, 'TOGGLE_USER_STATUS', 'Super Admin menonaktifkan akun staf Hendra Wijaya (@admin_hendra)', $this->u['admin_hendra'], -20);
    }

    private function extras(): void
    {
        $tag = ExtrasCategory::pluck('id', 'nama');
        $baju = ['pria' => 'L', 'wanita' => 'M'];

        // username, nama, email, gender, usia, tinggi, tags, grade, daftar (hari lalu), bahasa, pengalaman, rate card
        foreach ([
            ['spiderman', 'Tom Holland', 'dimas', 'pria', 23, 173, ['Dewasa muda', 'Mahasiswa', 'Naik motor', 'Sunda'], 'A', 90, 'Indonesia, Inggris', 'Aktor teater musikal West End & pemeran aksi akrobatik. Berpengalaman di film layar lebar dan komersial.', 250000],
            ['goyounjung', 'Go Youn-jung', 'sari', 'wanita', 21, 163, ['Dewasa muda', 'Berhijab', 'Mahasiswa'], 'B', 90, 'Indonesia, Korea', 'Model editorial majalah fashion dan aktris drama series. Berpengalaman di pemotretan komersial.', 225000],
            ['bruce_lee', 'Bruce Lee', 'bagas', 'pria', 22, 172, ['Dewasa muda', 'Atlet', 'Naik motor', 'Jawa'], 'A', 90, 'Indonesia, Inggris', 'Praktisi seni bela diri dan koreografer aksi laga. Berpengalaman di film aksi dan pertunjukan stunt.', 250000],
            ['zendaya', 'Zendaya', 'nadia', 'wanita', 24, 178, ['Dewasa muda', 'Rambut panjang', 'Menari'], 'C', 60, 'Indonesia, Inggris', 'Aktris musikal, penari profesional, dan model fashion global. Berpengalaman di panggung teater.', 200000],
            ['chalamet', 'Timothee Chalamet', 'rehan', 'pria', 19, 178, ['Remaja', 'Mahasiswa', 'Naik motor'], null, 60, 'Indonesia, Prancis', 'Aktor panggung teater off-Broadway dan film drama independen.', 200000],
            ['wonder_woman', 'Gal Gadot', 'citra', 'wanita', 25, 178, ['Dewasa muda', 'Berhijab', 'Menari'], 'B', 60, 'Indonesia, Ibrani', 'Model komersial internasional dan aktris film aksi laga. Pelatih kebugaran bersertifikat.', 200000],
            ['jackie_chan', 'Jackie Chan', 'harto', 'pria', 58, 174, ['Orang Tua', 'Jawa', 'Nyetir mobil'], 'A', 90, 'Indonesia, Mandarin', 'Aktor komedi laga legendaris, sutradara stunt, dan seniman bela diri akrobatik.', 300000],
            ['michelle_yeoh', 'Michelle Yeoh', 'ningsih', 'wanita', 52, 163, ['Orang Tua', 'Berhijab', 'Jawa'], null, 90, 'Indonesia, Melayu', 'Aktris laga internasional, penari balet, dan pemenang penghargaan film dunia.', 200000],
            ['chow_yun_fat', 'Chow Yun-fat', 'liem', 'pria', 67, 182, ['Lansia', 'Chinese/Tionghoa'], null, 20, 'Indonesia, Kantonis', 'Aktor legendaris perfilman Hong Kong dan drama aksi komersial.', 300000],
            ['zuckerberg', 'Mark Zuckerberg', 'kevin', 'pria', 27, 171, ['Dewasa', 'Chinese/Tionghoa', 'Pekerja kantoran'], 'B', 60, 'Indonesia, Inggris', 'Spesialis teknologi, praktisi Brazilian Jiu-Jitsu, dan pembicara konferensi digital.', 250000],
            ['bella_hadid', 'Bella Hadid', 'aisyah', 'wanita', 30, 175, ['Dewasa', 'Berhijab', 'Pekerja kantoran', 'Timur Tengah'], null, 60, 'Indonesia, Arab', 'Model runway internasional dan ikon kampanye brand kecantikan global.', 250000],
            ['iron_man', 'Robert Downey Jr', 'yohanes', 'pria', 28, 174, ['Dewasa', 'Indonesia Timur', 'Atlet', 'Berenang'], 'A', 60, 'Indonesia, Inggris', 'Aktor watak serbabisa dan produser film komersial layar lebar.', 300000],
            ['emma_watson', 'Emma Watson', 'clara', 'wanita', 26, 165, ['Dewasa', 'Kaukasia/Bule', 'Berenang'], null, 60, 'Inggris, Indonesia', 'Aktris lulusan sastra Brown University, aktivis, dan model kampanye mode.', 350000],
            ['batman', 'Christian Bale', 'fajar', 'pria', 33, 183, ['Dewasa', 'Bertato', 'Naik motor'], null, 60, 'Indonesia, Inggris', 'Aktor metode dengan transformasi fisik ekstrem untuk berbagai peran film intens.', 200000],
            ['song_hyekyo', 'Song Hye-kyo', 'wulan', 'wanita', 20, 161, ['Remaja', 'Mahasiswa', 'Sunda'], null, 45, 'Indonesia, Korea', 'Bintang drama televisi dan model komersial internasional brand kosmetik.', 200000],
            ['keanu_reeves', 'Keanu Reeves', 'tono', 'pria', 45, 186, ['Dewasa', 'Pekerja kantoran', 'Nyetir mobil'], 'B', 90, 'Indonesia, Inggris', 'Aktor film aksi bela diri, penggemar motor kustom, dan musisi.', 200000],
            ['black_widow', 'Scarlett Johansson', 'melati', 'wanita', 35, 160, ['Dewasa', 'Melayu', 'Menari'], null, 60, 'Indonesia, Inggris', 'Aktris panggung teater Broadway dan pengisi suara animasi komersial.', 250000],
            ['thor', 'Chris Hemsworth', 'arga', 'pria', 24, 190, ['Dewasa muda', 'Atlet', 'Berenang'], null, 60, 'Indonesia, Inggris', 'Aktor film laga, atlet kebugaran, dan praktisi olahraga selancar.', 250000],
            ['catwoman', 'Anne Hathaway', 'intan', 'wanita', 22, 173, ['Dewasa muda', 'Rambut panjang', 'Mahasiswa'], null, 60, 'Indonesia, Inggris', 'Aktris musikal pemenang piala Oscar dan penyanyi teater panggung.', 200000],
            ['dicaprio', 'Leonardo DiCaprio', 'joko', 'pria', 40, 183, ['Dewasa', 'Jawa', 'Naik motor'], null, 90, 'Indonesia, Inggris', 'Aktor drama watak papan atas dan pemeran karakter intens.', 200000],
            ['sydney_sweeney', 'Sydney Sweeney', 'putri', 'wanita', 23, 161, ['Dewasa muda', 'Berhijab'], null, 15, 'Indonesia, Inggris', 'Aktris drama televisi dan praktisi bela diri campuran (MMA).', 200000],
            ['brad_pitt', 'Brad Pitt', 'rahmat', 'pria', 29, 180, ['Dewasa'], null, 50, 'Indonesia, Inggris', 'Aktor film layar lebar dan produser pemenang penghargaan perfilman.', 200000],
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
                'foto_profil_path' => $this->fotoDemo($username, $no, $g),
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
            $profile->generateShareToken();
            $profile->categories()->sync($tag->only([...$tags, $usia > 50 ? 'Sawo matang' : 'Kuning langsat'])->values());
        }

        // BN.3: Favorit (kolom apresiasi).
        $this->u['spiderman']->extrasProfile->update(['apresiasi' => true, 'apresiasi_catatan' => 'Disukai Client, selalu on-time & gampang diarahkan.']);
        $this->u['jackie_chan']->extrasProfile->update(['apresiasi' => true, 'apresiasi_catatan' => 'Cocok peran bapak-bapak kantoran, on time.']);
        $this->u['goyounjung']->extrasProfile->update(['apresiasi' => true]);
        $this->u['dicaprio']->extrasProfile->forceFill(['status' => 'melanggar'])->save();
        $this->u['sydney_sweeney']->update(['status' => 'nonaktif']);
        $this->u['brad_pitt']->delete();

        // BH.2: Extras izin + tampil di beranda (maks 12), thor izin tapi belum di-ACC Admin.
        $berandaExtras = ['spiderman', 'goyounjung', 'bruce_lee', 'wonder_woman', 'jackie_chan', 'zuckerberg', 'iron_man', 'emma_watson', 'bella_hadid', 'zendaya', 'keanu_reeves', 'song_hyekyo'];
        foreach ($berandaExtras as $idx => $un) {
            $this->u[$un]->extrasProfile->forceFill([
                'izin_tampil_publik' => true,
                'tampil_di_beranda' => true,
                'tampil_di_beranda_at' => now()->subDays($idx),
            ])->save();
        }
        $this->u['thor']->extrasProfile->forceFill([
            'izin_tampil_publik' => true,
            'tampil_di_beranda' => false,
            'tampil_di_beranda_at' => null,
        ])->save();

        // CL/BJ: tautan tambahan (sosmed/portofolio/YouTube trailer) & galeri foto tambahan 4 slot multi-angle.
        $extrasMeta = [
            'spiderman' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Spider-Man: No Way Home', 'url' => 'https://www.youtube.com/watch?v=JfVOs4VSpmA'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/tomholland2013'],
                ],
            ],
            'goyounjung' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer Moving', 'url' => 'https://www.youtube.com/watch?v=O15d9yH4L3I'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/goyounjung'],
                ],
            ],
            'bruce_lee' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Enter the Dragon', 'url' => 'https://www.youtube.com/watch?v=tB_7kK9r_QY'],
                    ['label' => 'Official Site', 'url' => 'https://brucelee.com'],
                ],
            ],
            'zendaya' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer Dune: Part Two', 'url' => 'https://www.youtube.com/watch?v=Way9Dexny3w'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/zendaya'],
                ],
            ],
            'chalamet' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Wonka', 'url' => 'https://www.youtube.com/watch?v=otNh9bTjXWg'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/tchalamet'],
                ],
            ],
            'wonder_woman' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer Wonder Woman', 'url' => 'https://www.youtube.com/watch?v=1Q8fG9mpZZ8'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/gal_gadot'],
                ],
            ],
            'jackie_chan' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Police Story', 'url' => 'https://www.youtube.com/watch?v=Xk4r1Mph1b0'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/jackiechan'],
                ],
            ],
            'michelle_yeoh' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer Everything Everywhere All at Once', 'url' => 'https://www.youtube.com/watch?v=wxN1T1uxQ2g'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/michelleyeoh_official'],
                ],
            ],
            'chow_yun_fat' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Crouching Tiger, Hidden Dragon', 'url' => 'https://www.youtube.com/watch?v=gL790G_wRrk'],
                    ['label' => 'IMDb', 'url' => 'https://www.imdb.com/name/nm0000334/'],
                ],
            ],
            'zuckerberg' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer The Social Network', 'url' => 'https://www.youtube.com/watch?v=lB95KLmp1l4'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/zuck'],
                ],
            ],
            'bella_hadid' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Vogue Fashion Runway', 'url' => 'https://www.youtube.com/watch?v=q6c8p8PkWtQ'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/bellahadid'],
                ],
            ],
            'iron_man' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Iron Man', 'url' => 'https://www.youtube.com/watch?v=8ugaeA-nMTc'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/robertdowneyjr'],
                ],
            ],
            'emma_watson' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer Beauty and the Beast', 'url' => 'https://www.youtube.com/watch?v=e3Nl_TCQXuw'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/emmawatson'],
                ],
            ],
            'batman' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer The Dark Knight', 'url' => 'https://www.youtube.com/watch?v=EXeTwQWrcwY'],
                    ['label' => 'IMDb', 'url' => 'https://www.imdb.com/name/nm0000288/'],
                ],
            ],
            'song_hyekyo' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer The Glory', 'url' => 'https://www.youtube.com/watch?v=tqFFKDCSimA'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/kyo1122'],
                ],
            ],
            'keanu_reeves' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer John Wick: Chapter 4', 'url' => 'https://www.youtube.com/watch?v=qEVUtrk8_B4'],
                    ['label' => 'IMDb', 'url' => 'https://www.imdb.com/name/nm0000206/'],
                ],
            ],
            'black_widow' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer Black Widow', 'url' => 'https://www.youtube.com/watch?v=ybji16u608U'],
                    ['label' => 'IMDb', 'url' => 'https://www.imdb.com/name/nm0424060/'],
                ],
            ],
            'thor' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Thor: Ragnarok', 'url' => 'https://www.youtube.com/watch?v=ue80QwXMRHg'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/chrishemsworth'],
                ],
            ],
            'catwoman' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer The Intern', 'url' => 'https://www.youtube.com/watch?v=ZU3Xban0Y6A'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/annehathaway'],
                ],
            ],
            'dicaprio' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Inception', 'url' => 'https://www.youtube.com/watch?v=YoHD9XEInc0'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/leonardodicaprio'],
                ],
            ],
            'sydney_sweeney' => [
                'gender' => 'wanita',
                'tautan' => [
                    ['label' => 'Trailer Anyone But You', 'url' => 'https://www.youtube.com/watch?v=0h0971eL0f8'],
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/sydney_sweeney'],
                ],
            ],
            'brad_pitt' => [
                'gender' => 'pria',
                'tautan' => [
                    ['label' => 'Trailer Fight Club', 'url' => 'https://www.youtube.com/watch?v=qtRKdV9EIgw'],
                    ['label' => 'IMDb', 'url' => 'https://www.imdb.com/name/nm0000093/'],
                ],
            ],
        ];

        foreach ($extrasMeta as $un => $meta) {
            if (! isset($this->u[$un]) || ! $this->u[$un]->extrasProfile) {
                continue;
            }
            $g = $meta['gender'];
            $this->u[$un]->extrasProfile->forceFill([
                'tautan_tambahan' => $meta['tautan'],
                'foto_tambahan' => [
                    1 => $this->fotoDemo("{$un}-slot1", 20 + abs(crc32($un)) % 30, $g),
                    2 => $this->fotoDemo("{$un}-slot2", 21 + abs(crc32($un)) % 30, $g),
                    3 => $this->fotoDemo("{$un}-slot3", 22 + abs(crc32($un)) % 30, $g),
                    4 => $this->fotoDemo("{$un}-slot4", 23 + abs(crc32($un)) % 30, $g),
                ],
            ])->save();
        }

        $ghost = $this->akun('extras', 'ghost01', 'ghost01', 'ghost01@extras.test', now()->subDays(40));
        $ghost->extrasProfile()->create(['created_at' => now()->subDays(40)]);
        $tglHapus = now()->addDays(4)->translatedFormat('d F Y');
        $this->notif($ghost, 'Akun Akan Dihapus', "Profilmu belum lengkap. Lengkapi sebelum {$tglHapus} supaya akunmu nggak dihapus otomatis.", route('extras.profile.edit'), false, now()->subDays(3), ['jenis' => 'peringatan_mangkrak', 'kunci' => 'peringatan_mangkrak']);

        $rizka = $this->akun('extras', 'Anya Taylor-Joy', 'anya_taylor', 'rizka@extras.test', now()->subDays(2));
        $rizka->extrasProfile()->create(['nama_asli' => 'Anya Taylor-Joy', 'usia' => 21, 'gender' => 'wanita', 'tinggi_badan' => 173])
            ->categories()->sync($tag->only(['Dewasa muda'])->values());
    }

    private function proyek(PdfGeneratorService $pdf, KeuanganService $keuangan): void
    {
        [$fakrul, $rina, $yoga, $bambang, $dedi] = [$this->u['owner_jbtb'], $this->u['admin_rina'], $this->u['admin_yoga'], $this->u['korlap_bambang'], $this->u['korlap_dedi']];
        [$andini, $rudy, $maya] = [$this->u['client_andini'], $this->u['client_rudy'], $this->u['client_maya']];

        // P1: selesai
        $p1 = $this->project('Film "Rumah di Ujung Senja"', $rina, -45, ['deadline' => today()->subDays(35), 'kuota' => 7, 'status' => 'ditutup', 'client_id' => $andini->id]);
        $this->log($rina, 'CREATE_PROJECT', "Admin Rina Kartika membuat proyek '{$p1->nama_produksi}'", $p1, -45);
        $this->log($fakrul, 'UPDATE_PROJECT_PIC', "Super Admin menugaskan Rina Kartika sebagai PIC proyek '{$p1->nama_produksi}'", $p1, -45);
        $tgl1 = $this->jadwal($p1, [-30, -29, -28], 'Desa Cibodas, Lembang');
        $warga = $this->kelas($p1, 'Warga kampung', 4, 300000, ['Orang Tua', 'Jawa']);
        $mhsKos = $this->kelas($p1, 'Mahasiswa kos', 3, 350000, ['Dewasa muda', 'Mahasiswa']);

        foreach ([['jackie_chan', $warga, 200000], ['michelle_yeoh', $warga, 200000], ['keanu_reeves', $warga, 200000], ['dicaprio', $warga, 200000], ['spiderman', $mhsKos, 250000], ['bruce_lee', $mhsKos, 250000], ['goyounjung', $mhsKos, 225000]] as [$un, $kelas, $fee]) {
            $a = $this->daftar($p1, $kelas, $un, 'selesai_produksi', -40);
            $this->log($this->u[$un], 'APPLY_PROJECT', "Extras {$this->u[$un]->name} mendaftar untuk kelas '{$kelas->nama_kelas}' di proyek '{$p1->nama_produksi}'", $a, -40);
            $this->deal($a, $fee, -38);
            $this->greenlight($a, $andini, -36);
            if ($un === 'dicaprio') {
                $a->update(['status_partisipasi' => 'dibatalkan']);
                $a->cancellations()->create(['dibatalkan_oleh' => 'extras', 'alasan' => 'Ada acara keluarga mendadak.', 'is_mendadak' => true, 'created_at' => now()->subDays(31)]);

                continue;
            }
            $this->kontrak($pdf, $a, $rina, true, -33);
            foreach ($tgl1 as $t) {
                $a->attendances()->create(['event_shooting_date_id' => $t->id, 'status' => 'hadir', 'dicatat_oleh' => $bambang->id, 'status_validasi' => 'tervalidasi', 'divalidasi_oleh' => $bambang->id, 'divalidasi_at' => $t->tanggal->copy()->setTime(7, 30)]);
            }
            $status = match ($un) {
                'keanu_reeves' => 'ditransfer',
                'goyounjung' => 'disengketakan',
                default => 'dikonfirmasi_diterima',
            };
            $pay = $this->bayar($a, $status, -25, $rina);
            if ($un === 'spiderman') {
                $pay->addons()->create(['label' => 'Transport', 'nominal' => 50000, 'created_by' => $rina->id]);
            }
        }
        $this->app($p1, 'jackie_chan')->tambahCatatan($bambang, 'catatan', 'Datang paling pagi tiap hari, bantu arahkan extras lain.');
        $this->app($p1, 'spiderman')->tambahCatatan($bambang, 'catatan', 'Improvisasi bagus di scene warung kopi.');
        $this->app($p1, 'dicaprio')->tambahCatatan($bambang, 'sanksi', 'Batal H-1 tanpa pengganti, pembatalan mendadak ke-3.');
        $this->log($rina, 'CALLSHEET_EXPORT', "Admin Rina Kartika mengekspor callsheet untuk lokasi Desa Cibodas, Lembang di proyek '{$p1->nama_produksi}'", $p1, -30);
        $this->log($this->u['goyounjung'], 'DISPUTE_PAYMENT', "Extras Sari Meilani menandai pembayaran sebagai sengketa untuk proyek '{$p1->nama_produksi}'", $this->app($p1, 'goyounjung'), -24);

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
            'deadline' => today()->addDays(3), 'kuota' => 15, 'client_id' => $andini->id, 'budget_total' => 3000000,
            'brief_catatan' => 'Iklan TV 30 detik minuman isotonik, suasana kampus & kantor.', 'link_grup' => 'https://chat.whatsapp.com/demo-minuman-segar',
        ]);
        [$jadwalP2a] = $this->jadwal($p2, [5, 6], 'Kampus UI Depok');
        $jadwalP2a->update([
            'catatan' => 'Parkir kendaraan di gedung PKM, briefing koordinator jam 05.30.',
            'panggilan' => [['nama' => 'Tim make up', 'jam' => '05:00'], ['nama' => 'Tim wardrobe', 'jam' => '05:15']],
        ]);
        $mhs = $this->kelas($p2, 'Mahasiswa kampus', 5, 300000, ['Dewasa muda', 'Mahasiswa', 'Naik motor']);
        $mhs->update(['keterangan_scene' => 'Baju kampus kasual, bawa tas ransel', 'tipe_continuity' => 'free']);
        $kantor = $this->kelas($p2, 'Pekerja kantoran', 3, 350000, ['Dewasa', 'Pekerja kantoran']);
        $kantor->update(['keterangan_scene' => 'Kemeja formal, id card kantor', 'tipe_continuity' => 'continuity']);
        foreach ([$rina, $dedi] as $staf) {
            $p2->adminAssignments()->create(['user_id' => $staf->id, 'assigned_by' => $fakrul->id]);
        }
        $this->log($andini, 'SUBMIT_PROJECT_REQUEST', "Client Andini Prameswari mengajukan proyek '{$p2->nama_produksi}'", $p2, -8);
        $this->log($fakrul, 'APPROVE_PROJECT_REQUEST', "Super Admin menyetujui (ACC) permintaan proyek '{$p2->nama_produksi}' dari Client", $p2, -7);

        $this->daftar($p2, $mhs, 'song_hyekyo', 'diajukan', 0);
        $this->log($this->u['song_hyekyo'], 'APPLY_PROJECT', "Extras Wulan Sari mendaftar untuk kelas '{$mhs->nama_kelas}' di proyek '{$p2->nama_produksi}'", $this->app($p2, 'song_hyekyo'), 0);
        $this->daftar($p2, $mhs, 'catwoman', 'direview_admin', -2)->update(['grade' => 'B']);
        $this->log($this->u['catwoman'], 'APPLY_PROJECT', "Extras Intan Rosalina mendaftar untuk kelas '{$mhs->nama_kelas}' di proyek '{$p2->nama_produksi}'", $this->app($p2, 'catwoman'), -2);
        $this->log($rina, 'SET_EXTRAS_GRADE', 'Admin Rina Kartika memberikan evaluasi Grade B untuk Intan Rosalina', $this->u['catwoman']->extrasProfile, -2);
        $rehan = $this->daftar($p2, $mhs, 'chalamet', 'nego_fee', -3);
        $this->log($this->u['chalamet'], 'APPLY_PROJECT', "Extras Rehan Saputra mendaftar untuk kelas '{$mhs->nama_kelas}' di proyek '{$p2->nama_produksi}'", $rehan, -3);
        $rehan->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 150000, 'aksi' => 'tawar', 'created_at' => now()->subDays(2)]);
        $rehan->feeNegotiations()->create(['round' => 2, 'diajukan_oleh' => 'extras', 'nominal' => 200000, 'aksi' => 'counter', 'catatan' => 'Lokasi jauh dari rumah, mohon dinaikkan.', 'created_at' => now()->subDay()]);
        $this->log($rina, 'NEGOTIATE_FEE', 'Admin Rina mengajukan penawaran fee Rp 150.000 kepada Rehan Saputra', $rehan, -2);
        $this->log($this->u['chalamet'], 'NEGOTIATE_FEE', 'Extras Rehan Saputra mengajukan counter fee Rp 200.000', $rehan, -1);
        $arga = $this->daftar($p2, $mhs, 'thor', 'nego_fee', -3);
        $arga->feeNegotiations()->create(['round' => 1, 'diajukan_oleh' => 'admin', 'nominal' => 175000, 'aksi' => 'tawar', 'created_at' => now()->subDay()]);
        $this->deal($this->daftar($p2, $mhs, 'wonder_woman', 'deal', -4), 175000, -2);
        $this->log($this->u['wonder_woman'], 'DEAL_FEE', "Extras Citra Ayu menyetujui fee Rp 175.000 untuk proyek '{$p2->nama_produksi}'", $this->app($p2, 'wonder_woman'), -2);
        $this->deal($this->daftar($p2, $mhs, 'spiderman', 'diajukan_ke_client', -5), 250000, -3);
        $this->deal($this->daftar($p2, $kantor, 'zuckerberg', 'diajukan_ke_client', -5), 250000, -3);
        $bagas = $this->daftar($p2, $mhs, 'bruce_lee', 'lolos', -6);
        $this->deal($bagas, 250000, -5);
        $this->greenlight($bagas, $andini, -4);
        $this->kontrak($pdf, $bagas, $rina, false, -1);
        $aisyah = $this->daftar($p2, $kantor, 'bella_hadid', 'kontrak_ditandatangani', -6);
        $this->deal($aisyah, 250000, -5);
        $this->greenlight($aisyah, $andini, -4);
        $this->kontrak($pdf, $aisyah, $rina, true, -2);
        $this->bayar($aisyah, 'belum_dibayar', -2, $rina);
        // QA: lolos, kontrak sengaja TIDAK dibuat manual lewat kontrak() - diisi otomatis oleh
        // siapkanKontrakDanPembayaran() di akhir run(), hasilnya kontrak kosong, belum ada TTD sama sekali.
        $tono2 = $this->daftar($p2, $kantor, 'keanu_reeves', 'lolos', -4);
        $this->deal($tono2, 300000, -3);
        $this->greenlight($tono2, $andini, -2);
        $this->daftar($p2, $mhs, 'zendaya', 'ditolak', -5)->update(['alasan_tolak' => 'Tinggi tidak sesuai kriteria.']);
        $this->daftar($p2, $mhs, 'goyounjung', 'diundang', -1)->update(['diundang_at' => now()->subDay()]);
        $this->log($rina, 'INVITE_EXTRAS', "Admin Rina mengundang @goyounjung ke proyek '{$p2->nama_produksi}'", $this->app($p2, 'goyounjung'), -1);
        $fajar = $this->daftar($p2, $kantor, 'batman', 'dibatalkan', -6);
        $this->deal($fajar, 200000, -5);
        $fajar->cancellations()->create(['dibatalkan_oleh' => 'extras', 'alasan' => 'Bentrok jadwal kerja.', 'is_mendadak' => false, 'created_at' => now()->subDays(3)]);
        $p2->invoices()->create([]);
        $this->biaya($p2, $rina, [['DP konsumsi', 300000, -1]]);

        // P3: shooting hari ini & besok
        // CN.3.2: budget_total < alokasi (300000*6 + 400000*2 = 2.600.000) -> demo peringatan alokasi melebihi budget.
        $p3 = $this->project('Series "Kampus Biru" eps 1-2', $yoga, -20, ['deadline' => today()->subDays(5), 'kuota' => 8, 'status' => 'ditutup', 'link_grup' => 'https://chat.whatsapp.com/demo-kampus-biru', 'client_id' => $rudy->id, 'budget_total' => 2400000]);
        $this->log($yoga, 'CREATE_PROJECT', "Admin Yoga Pratama membuat proyek '{$p3->nama_produksi}'", $p3, -20);
        $this->log($fakrul, 'UPDATE_PROJECT_PIC', "Super Admin menugaskan Yoga Pratama sebagai PIC proyek '{$p3->nama_produksi}'", $p3, -20);
        [$hariIni] = $this->jadwal($p3, [0, 1], 'Kampus Universitas Pamulang');
        $hariIni->update([
            'catatan' => 'Bawa jas almamater sendiri kalau punya, kalau tidak dipinjamkan di lokasi.',
            'panggilan' => [['nama' => 'Tim kamera', 'jam' => '05:00'], ['nama' => 'Tim lighting', 'jam' => '05:00']],
        ]);
        $mhs3 = $this->kelas($p3, 'Mahasiswa', 6, 300000, ['Mahasiswa']);
        $mhs3->update(['keterangan_scene' => 'Jas almamater, sepatu kets', 'tipe_continuity' => 'continuity']);
        $dosen = $this->kelas($p3, 'Dosen', 2, 400000, ['Orang Tua']);
        $dosen->update(['keterangan_scene' => 'Kemeja batik, sepatu pantofel', 'tipe_continuity' => 'free']);
        foreach ([$yoga, $bambang] as $staf) {
            $p3->adminAssignments()->create(['user_id' => $staf->id, 'assigned_by' => $fakrul->id, 'created_at' => now()->subDays(20)]);
        }
        $intanApp = null;
        foreach (['iron_man', 'emma_watson', 'black_widow', 'catwoman', 'jackie_chan', 'song_hyekyo', 'bruce_lee'] as $un) {
            $a = $this->daftar($p3, $un === 'jackie_chan' ? $dosen : $mhs3, $un, 'kontrak_ditandatangani', -15);
            $this->deal($a, $un === 'jackie_chan' ? 300000 : 200000, -12);
            $this->greenlight($a, $rudy, -10);
            $this->kontrak($pdf, $a, $yoga, true, -7);
            $status = match ($un) {
                'emma_watson', 'bruce_lee' => 'ditransfer', 'black_widow' => 'disengketakan', default => 'belum_dibayar'
            };
            $pay = $this->bayar($a, $status, 0, $yoga);
            if ($un === 'catwoman') {
                $intanApp = $a;
                // CS.1: demo override jam kumpul per pendaftaran, beda dari default peran (06:00).
                $a->update(['jam_callingan_override' => '05:30']);
            }
            if ($un === 'black_widow') {
                // CA: contoh sengketa yang SUDAH diselesaikan (beda dari goyounjung di P1 yang sengaja dibiarkan belum selesai).
                $this->log($this->u[$un], 'DISPUTE_PAYMENT', "Extras Melati Kusuma menandai pembayaran sebagai sengketa untuk proyek '{$p3->nama_produksi}'", $a, 0);
                $pay->selesaikanSengketa('Sudah dicek ulang ke rekap keuangan, nominal sudah sesuai kesepakatan awal.');
                $this->log($yoga, 'RESOLVE_DISPUTE', "Admin Yoga Pratama menyelesaikan sengketa pembayaran proyek '{$p3->nama_produksi}'", $a, 0);
            }

            match ($un) {
                'iron_man', 'emma_watson' => $this->selfie($a, $hariIni, $bambang),
                'black_widow', 'catwoman' => $this->selfie($a, $hariIni),
                'jackie_chan' => $a->attendances()->create(['event_shooting_date_id' => $hariIni->id, 'status' => 'tidak_hadir', 'dicatat_oleh' => $bambang->id, 'catatan' => 'Izin sakit, dikabari via telepon jam 06.00.', 'status_validasi' => 'tervalidasi', 'divalidasi_oleh' => $bambang->id, 'divalidasi_at' => now()]),
                'bruce_lee' => $this->selfieDitolak($a, $hariIni, $bambang),
                default => null,
            };
        }

        $this->biaya($p3, $yoga, [['Transport korlap', 150000, 0]]);

        // CO.2: demo "sudah diingatkan" - SA mengingatkan Yoga soal honor Intan yang belum ditransfer.
        if ($intanApp) {
            $tugasIntan = "Honor belum ditransfer di {$p3->nama_produksi}";
            $this->notif($yoga, 'Pengingat dari Super Admin', "Super Admin mengingatkan: {$tugasIntan}",
                route('payments.show', $intanApp, false), false, now(), ['jenis' => 'pengingat_sa', 'kunci' => 'pa-'.$intanApp->id]);
            $this->log($fakrul, 'REMIND_ADMIN', "Super Admin mengingatkan Yoga Pratama: {$tugasIntan}", $intanApp, 0);
        }

        // P4: pengajuan Client, menunggu ACC - CN.1 lengkap (peran, tanggal+lokasi, budget total) sejak diajukan.
        $p4 = CastingProject::create([
            'nama_produksi' => 'Video Klip "Nadaria"', 'share_token' => Str::random(32),
            'deadline' => today()->addDays(14), 'kuota' => 20, 'budget_total' => 15000000,
            'brief_catatan' => 'Video klip single baru, konsep konser panggung outdoor.',
            'client_id' => $maya->id, 'client_request_status' => 'menunggu_acc', 'status' => 'ditutup',
            'created_at' => now()->subDay(),
        ]);
        $p4->classes()->create(['nama_kelas' => 'Penonton Konser', 'kuota_kelas' => 15, 'kriteria' => 'Usia 18-30, antusias, bisa joget']);
        $p4->classes()->create(['nama_kelas' => 'Crew Panggung', 'kuota_kelas' => 5, 'kriteria' => 'Pria dewasa, badan tegap']);
        $p4->shootingDates()->create(['tanggal' => today()->addDays(16), 'lokasi' => 'Lapangan Parkir Timur, GBK Senayan']);
        $this->log($maya, 'SUBMIT_PROJECT_REQUEST', "Client Maya Salsabila mengajukan proyek '{$p4->nama_produksi}'", $p4, -1);

        // P4b: pengajuan Client, diminta revisi SA - CN.2.1.2 (Client lupa lengkapi lokasi shooting).
        $p4b = CastingProject::create([
            'nama_produksi' => 'Iklan "Sampo Berkilau"', 'share_token' => Str::random(32),
            'deadline' => today()->addDays(18), 'kuota' => 6, 'budget_total' => 4500000,
            'brief_catatan' => 'Iklan sampo, konsep rambut panjang berkilau di kolam renang.',
            'client_id' => $rudy->id, 'client_request_status' => 'perlu_revisi', 'status' => 'ditutup',
            'alasan_tolak' => 'Lokasi shooting tanggal ke-2 belum diisi, mohon lengkapi dulu sebelum diajukan ulang.',
            'created_at' => now()->subDays(2),
        ]);
        $p4b->classes()->create(['nama_kelas' => 'Model Wanita', 'kuota_kelas' => 6, 'kriteria' => 'Wanita 20-28 th, rambut panjang sehat']);
        $p4b->shootingDates()->create(['tanggal' => today()->addDays(20), 'lokasi' => 'Kolam Renang Senayan']);
        $p4b->shootingDates()->create(['tanggal' => today()->addDays(21)]);
        $this->log($rudy, 'SUBMIT_PROJECT_REQUEST', "Client Rudy Hartono mengajukan proyek '{$p4b->nama_produksi}'", $p4b, -2);
        $this->log($fakrul, 'REQUEST_REVISION_PROJECT', "Super Admin meminta revisi permintaan proyek '{$p4b->nama_produksi}' dari Client", $p4b, -1);

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
        $p6 = $this->project('Iklan "Bank Digital"', $yoga, -1, ['deadline' => today()->addDays(7), 'kuota' => 10, 'is_urgent' => true, 'nego_terbuka' => false]);
        $this->jadwal($p6, [10], 'SCBD, Jakarta Selatan');
        $this->kelas($p6, 'Nasabah muda', 10, 450000, ['Dewasa', 'Pekerja kantoran']);
        $p6->adminAssignments()->create(['user_id' => $yoga->id, 'assigned_by' => $fakrul->id]);
        $this->log($yoga, 'CREATE_PROJECT', "Admin Yoga Pratama membuka lowongan casting dadakan '{$p6->nama_produksi}'", $p6, -1);

        // P7: fee tetap (nego_terbuka=false), tetap ada pendaftar - demo badge "Fee Tetap" tanpa menghapus P6 yang sengaja kosong.
        $p7 = $this->project('Iklan "Kopi Instan Semangat"', $yoga, -3, [
            'deadline' => today()->addDays(10), 'kuota' => 5, 'client_id' => $maya->id, 'nego_terbuka' => false,
        ]);
        $this->jadwal($p7, [12], 'Senayan, Jakarta Pusat');
        $kelasP7 = $this->kelas($p7, 'Pekerja Kantoran', 5, 275000, ['Dewasa', 'Pekerja kantoran']);
        $p7->adminAssignments()->create(['user_id' => $yoga->id, 'assigned_by' => $fakrul->id]);
        $this->daftar($p7, $kelasP7, 'keanu_reeves', 'diajukan', -2);
        $this->deal($this->daftar($p7, $kelasP7, 'black_widow', 'deal', -3), 275000, -1);

        // CQ: proyek "evergreen" tersebar beberapa bulan ke belakang & ke depan (relatif ke tanggal seed
        // dijalankan), biar filter periode (7/14/30/60/90/365 hari, +7/+30, custom) beneran kelihatan
        // bedanya tiap preset - bukan cuma menumpuk di ±1-2 bulan seperti P1-P7 di atas.
        // P8-P10: selesai produksi, lengkap kontrak TTD + pembayaran dikonfirmasi (bukan cuma daftar mentah).
        foreach ([
            ['P8', 'Iklan "Sarapan Sehat"', $yoga, $andini, -58, 'Selesai 2 bulan lalu'],
            ['P9', 'FTV "Pulang ke Rumah"', $rina, $rudy, -88, 'Selesai 3 bulan lalu'],
            ['P10', 'Iklan "Skincare Glowing"', $yoga, $maya, -148, 'Selesai 5 bulan lalu'],
        ] as [$kode, $nama, $pic, $klien, $hari, $catatan]) {
            $ev = $this->project($nama, $pic, $hari - 10, [
                'deadline' => today()->addDays($hari - 5), 'kuota' => 6, 'client_id' => $klien->id,
                'status' => 'ditutup', 'brief_catatan' => "{$catatan} ({$kode} evergreen).", 'budget_total' => 2000000,
            ]);
            $this->jadwal($ev, [$hari], 'Jakarta');
            $kelasEv = $this->kelas($ev, 'Pemeran Pendukung', 6, 250000, ['Dewasa']);
            $a = $this->daftar($ev, $kelasEv, 'song_hyekyo', 'selesai_produksi', $hari - 15);
            $this->deal($a, 250000, $hari - 12);
            $this->kontrak($pdf, $a, $pic, true, $hari - 9);
            $this->bayar($a, 'dikonfirmasi_diterima', $hari + 5, $pic);

            if ($kode === 'P9') {
                // RF-08: batal mendadak ke-2 Fajar Nugroho di proyek berbeda, belum sampai ambang 3x (belum melanggar).
                $batalFajar = $this->daftar($ev, $kelasEv, 'batman', 'dibatalkan', $hari - 14);
                $batalFajar->cancellations()->create(['dibatalkan_oleh' => 'extras', 'alasan' => 'Demam tinggi mendadak H-1.', 'is_mendadak' => true, 'created_at' => now()->addDays($hari - 13)]);
                $this->u['batman']->extrasProfile->recordCancellation();

                // Audit item 4: Lestari pernah jadi PIC proyek ini, dicabut & diganti $pic, honor lamanya tetap tersimpan.
                $asgLama = $ev->adminAssignments()->create(['user_id' => $this->u['admin_lestari']->id, 'assigned_by' => $fakrul->id, 'created_at' => now()->addDays($hari - 10)]);
                $payrollLama = $asgLama->tandaiSelesai();
                $asgLama->update(['completed_at' => now()->addDays($hari - 9)]);
                $payrollLama->tandaiDibayar();
                $asgLama->update(['dicabut_at' => now()->addDays($hari - 4)]);
                $ev->adminAssignments()->create(['user_id' => $pic->id, 'assigned_by' => $fakrul->id, 'created_at' => now()->addDays($hari - 4)]);
                $this->log($fakrul, 'UPDATE_PROJECT_PIC', "Super Admin mengganti PIC proyek '{$ev->nama_produksi}' dari Lestari Wulan ke {$pic->name}, riwayat honor Lestari tetap tersimpan", $ev, $hari - 4);
            }
        }
        foreach ([
            ['P11', 'Series "Jalan Pulang" eps 1', $yoga, $andini, 45, 'Shooting 1,5 bulan lagi'],
            ['P12', 'Iklan "Kartu Kredit Pintar"', $rina, $rudy, 75, 'Shooting 2,5 bulan lagi'],
        ] as [$kode, $nama, $pic, $klien, $hari, $catatan]) {
            $ev = $this->project($nama, $pic, -1, [
                'deadline' => today()->addDays($hari - 10), 'kuota' => 6, 'client_id' => $klien->id,
                'brief_catatan' => "{$catatan} ({$kode} evergreen).", 'budget_total' => 2000000,
            ]);
            $this->jadwal($ev, [$hari], 'Jakarta');
            $kelasEv = $this->kelas($ev, 'Pemeran Pendukung', 6, 250000, ['Dewasa']);
            $ev->adminAssignments()->create(['user_id' => $pic->id, 'assigned_by' => $fakrul->id]);

            if ($kode === 'P11') {
                // RF-08: batal mendadak ke-1 Fajar Nugroho (genap jadi 2 bareng P9, masih di bawah ambang 3x).
                $batalFajar = $this->daftar($ev, $kelasEv, 'batman', 'dibatalkan', -2);
                $batalFajar->cancellations()->create(['dibatalkan_oleh' => 'extras', 'alasan' => 'Kecelakaan motor ringan menuju lokasi survei.', 'is_mendadak' => true, 'created_at' => now()->subDays(1)]);
                $this->u['batman']->extrasProfile->recordCancellation();
            }
        }

        // BH.3: portofolio beranda, P1 + 2 arsip lama (satu izinkan nama client)
        $p1->update(['tampil_portofolio' => true, 'portofolio_jenis' => 'Film layar lebar', 'portofolio_tahun' => today()->subDays(30)->year]);
        foreach ([['Iklan TV "Kopi Pagi Nusantara"', 'PT Kopi Pagi Nusantara', 'Iklan TV', -200, true], ['FTV "Cinta di Pasar Minggu"', 'Sinar Rumah Produksi', 'FTV', -320, false]] as [$nama, $ph, $jenis, $hari, $client]) {
            $klien = $this->akun('client', $ph, 'client_'.Str::slug($ph, '_'), Str::slug($ph).'@arsip.test', now()->addDays($hari - 30), ['nama_perusahaan' => $ph, 'status' => 'nonaktif']);
            $arsip = $this->project($nama, $rina, $hari - 20, [
                'client_id' => $klien->id, 'status' => 'ditutup', 'deadline' => today()->addDays($hari - 5), 'kuota' => 10, 'tampil_portofolio' => true,
                'portofolio_jenis' => $jenis, 'portofolio_tahun' => today()->addDays($hari)->year, 'tampilkan_nama_client' => $client,
            ]);
            $this->jadwal($arsip, [$hari], 'Jakarta');
            // RF-08: 2 batal mendadak lama dicaprio (yang ke-3 di P1) -> hitungan dari tabel cancellations.
            $this->daftar($arsip, $this->kelas($arsip, 'Warga', 10, 300000, ['Dewasa']), 'dicaprio', 'dibatalkan', $hari - 10)
                ->cancellations()->create(['dibatalkan_oleh' => 'extras', 'alasan' => 'Sakit mendadak.', 'is_mendadak' => true, 'created_at' => now()->addDays($hari - 1)]);
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
            ['owner_jbtb', 'Pembayaran Disengketakan', "Sari Meilani menyengketakan honor proyek '{$p1->nama_produksi}'.", $bayar($p1, 'goyounjung'), false],
            ['owner_jbtb', 'Pengajuan Proyek Baru', "Client Maya mengajukan proyek '{$p4->nama_produksi}', menunggu ACC.", route('super-admin.dashboard'), false],
            ['admin_rina', 'Counter Fee dari Extras', "chalamet mengajukan counter Rp 200.000 di proyek '{$p2->nama_produksi}'. Giliran kamu.", route('admin.negotiations.show', $this->app($p2, 'chalamet')), false],
            ['admin_rina', 'Pembayaran Disengketakan', "Sari Meilani: \"nominal kurang 1 hari\" di proyek '{$p1->nama_produksi}'.", $bayar($p1, 'goyounjung'), false],
            ['admin_rina', 'Honor Dibayar', "Honor kamu untuk proyek '{$p1->nama_produksi}' sudah dibayar.", null, true],
            ['admin_yoga', 'Selfie Menunggu Validasi', "2 selfie absensi '{$p3->nama_produksi}' hari ini menunggu validasi Korlap.", route('admin.attendance.index', ['project' => $p3->id]), false],
            ['admin_yoga', 'Lowongan Dibuka', "Lowongan '{$p6->nama_produksi}' sudah tayang, belum ada pendaftar.", route('admin.projects.applicants', $p6), true],
            ['admin_lestari', 'Akun Admin Dibuat', 'Akun admin kamu sudah aktif. Tunggu penugasan proyek dari Super Admin.', null, false],
            ['admin_hendra', 'Akun Dinonaktifkan', 'Akun kamu dinonaktifkan Super Admin.', null, false],
            ['korlap_bambang', 'Selfie Menunggu Validasi', "black_widow & catwoman mengirim selfie absensi '{$p3->nama_produksi}'.", route('admin.attendance.index', ['project' => $p3->id]), false],
            ['korlap_bambang', 'Honor Tercatat', "Slip honor proyek '{$p1->nama_produksi}' sudah dibuat, menunggu pembayaran.", null, true],
            ['korlap_dedi', 'Penugasan Proyek', "Kamu ditugaskan sebagai Korlap di proyek '{$p2->nama_produksi}'.", null, false],
            ['client_andini', 'Kandidat Menunggu Greenlight', "2 kandidat proyek '{$p2->nama_produksi}' menunggu keputusan kamu.", route('client.reviews.show', $p2), false],
            ['client_andini', 'Invoice Siap Diunduh', "Invoice proyek '{$p1->nama_produksi}' sudah ditandatangani kedua pihak.", route('invoices.show', $p1), true],
            ['client_andini', 'Pengajuan Proyek Disetujui', "Pengajuan proyek '{$p2->nama_produksi}' disetujui tim JBTB.", route('client.dashboard'), true],
            ['client_rudy', 'Pengajuan Proyek Ditolak', "Pengajuan proyek '{$p5->nama_produksi}' ditolak. Alasan: {$p5->alasan_tolak}", route('client.dashboard'), false],
            ['client_rudy', 'Shooting Hari Ini', "Shooting '{$p3->nama_produksi}' berlangsung hari ini, pantau foto kehadiran.", route('client.jadwal.show', $p3), false],
            ['client_maya', 'Pengajuan Terkirim', "Pengajuan proyek '{$p4->nama_produksi}' terkirim, menunggu ACC Super Admin.", route('client.dashboard'), true],
            ['spiderman', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'spiderman'), true],
            ['spiderman', 'Diajukan ke Client', "Kamu diajukan ke Client untuk proyek '{$p2->nama_produksi}'.", route('extras.dashboard'), false],
            ['goyounjung', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'goyounjung'), true],
            ['goyounjung', 'Sengketa Dicatat', 'Laporan sengketa kamu sudah diterima Admin.', $bayar($p1, 'goyounjung'), false],
            ['bruce_lee', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'bruce_lee'), true],
            ['bruce_lee', 'Kontrak Siap Ditandatangani', "Kontrak proyek '{$p2->nama_produksi}' sudah ditandatangani Admin. Giliran kamu.", $kontrak($p2, 'bruce_lee'), false, ['jenis' => 'kontrak_siap_ttd', 'email' => 'terkirim', 'wa' => 'terkirim', 'wa_dikirim_at' => now()->toDateTimeString()]],
            ['zendaya', 'Hasil Seleksi', "Mohon maaf, kamu belum lolos seleksi proyek '{$p2->nama_produksi}' kali ini.", route('extras.dashboard'), false, ['jenis' => 'hasil_seleksi', 'email' => 'terkirim', 'wa' => 'gagal', 'wa_dikirim_at' => now()->toDateTimeString()]],
            ['chalamet', 'Penawaran Fee', "Admin menawarkan Rp 150.000 untuk proyek '{$p2->nama_produksi}'.", $nego('chalamet'), true, ['jenis' => 'nego_fee', 'email' => 'terkirim']],
            ['thor', 'Penawaran Fee', "Admin menawarkan Rp 175.000 untuk proyek '{$p2->nama_produksi}'. Terima atau ajukan counter.", $nego('thor'), false],
            ['wonder_woman', 'Fee Deal', "Fee Rp 175.000 untuk proyek '{$p2->nama_produksi}' sudah deal.", $nego('wonder_woman'), false],
            ['jackie_chan', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'jackie_chan'), true],
            ['jackie_chan', 'Kontrak Ditandatangani', "Kontrak proyek '{$p3->nama_produksi}' sudah lengkap.", $kontrak($p3, 'jackie_chan'), true],
            ['michelle_yeoh', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer.", $bayar($p1, 'michelle_yeoh'), true],
            ['zuckerberg', 'Diajukan ke Client', "Kamu diajukan ke Client untuk proyek '{$p2->nama_produksi}'.", route('extras.dashboard'), false],
            ['bella_hadid', 'Kontrak Ditandatangani', "Kontrak proyek '{$p2->nama_produksi}' sudah lengkap.", $kontrak($p2, 'bella_hadid'), false],
            ['iron_man', 'Absensi Tervalidasi', "Kehadiran kamu di '{$p3->nama_produksi}' hari ini sudah divalidasi Korlap.", route('extras.dashboard'), false],
            ['emma_watson', 'Pembayaran Ditransfer', "Honor proyek '{$p3->nama_produksi}' sudah ditransfer. Silakan konfirmasi.", $bayar($p3, 'emma_watson'), false],
            ['batman', 'Pembatalan Tercatat', "Pembatalan kamu untuk proyek '{$p2->nama_produksi}' sudah tercatat.", route('extras.dashboard'), true],
            ['song_hyekyo', 'Pendaftaran Diterima', "Pendaftaran proyek '{$p2->nama_produksi}' berhasil, Admin akan mereview.", route('extras.dashboard'), true, ['jenis' => 'konfirmasi_apply', 'wa' => 'terkirim', 'wa_dikirim_at' => now()->toDateTimeString()]],
            ['song_hyekyo', 'Shooting Hari Ini', "Shooting '{$p3->nama_produksi}' hari ini. Jangan lupa selfie absensi.", route('extras.dashboard'), false],
            ['keanu_reeves', 'Pembayaran Ditransfer', "Honor proyek '{$p1->nama_produksi}' sudah ditransfer. Silakan konfirmasi.", $bayar($p1, 'keanu_reeves'), false],
            ['black_widow', 'Selfie Terkirim', 'Selfie absensi kamu menunggu validasi Korlap.', route('extras.dashboard'), false],
            ['catwoman', 'Selfie Terkirim', 'Selfie absensi kamu menunggu validasi Korlap.', route('extras.dashboard'), false],
            ['dicaprio', 'Status Akun Melanggar', 'Akun kamu berstatus Melanggar karena 3x batal mendadak.', null, false],
            ['sydney_sweeney', 'Akun Dinonaktifkan', 'Akun kamu dinonaktifkan Admin.', null, false],
            ['ghost01', 'Lengkapi Profil', 'Profil kamu masih kosong, lengkapi supaya bisa daftar lowongan.', route('extras.profile.edit'), false],
            ['anya_taylor', 'Lengkapi Profil', 'Upload foto profil supaya bisa daftar lowongan.', route('extras.profile.edit'), false],
        ] as $baris) {
            [$un, $judul, $pesan, $url, $dibaca, $kirim] = $baris + [5 => []];
            $this->notif($this->u[$un], $judul, $pesan, $url, $dibaca, kirim: $kirim);
        }

        foreach ($this->u as $un => $user) {
            if ($user->isExtras() && ! in_array($un, ['ghost01', 'anya_taylor'], true)) {
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
        return $this->u[$username] = User::create($extra + [
            'role' => $role, 'name' => $nama, 'username' => $username, 'email' => $email,
            'password' => $this->password, 'status' => 'aktif', 'email_verified_at' => $at, 'created_at' => $at,
        ]);
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
        $a->clientReviews()->create(['client_id' => $client->id, 'keputusan' => 'approve', 'grade_client' => 'A', 'created_at' => now()->addDays($hari)]);
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
        $fotoPath = "absensi/{$a->id}/demo-selfie.png";
        $profileFoto = $a->extras->foto_profil_path;
        if ($profileFoto && Storage::disk('local')->exists($profileFoto)) {
            $ext = pathinfo($profileFoto, PATHINFO_EXTENSION);
            $fotoPath = "absensi/{$a->id}/demo-selfie.{$ext}";
            Storage::disk('local')->put($fotoPath, Storage::disk('local')->get($profileFoto));
        } else {
            $this->png($fotoPath, 'Selfie Demo');
        }

        $absen = $a->attendances()->create([
            'event_shooting_date_id' => $tgl->id, 'status' => 'hadir', 'dicatat_oleh' => $user->id,
            'foto_path' => $fotoPath,
            'catatan' => 'Selfie absensi kehadiran Extras di lokasi shooting',
            'status_validasi' => $korlap ? 'tervalidasi' : 'menunggu',
            'divalidasi_oleh' => $korlap?->id, 'divalidasi_at' => $korlap ? now() : null,
        ]);
        $this->log($user, 'SUBMIT_SELFIE_ATTENDANCE', "Extras {$user->name} mengirimkan selfie absensi di lokasi shooting untuk proyek {$a->castingProject->nama_produksi}", $absen, 0);
        if ($korlap) {
            $this->log($korlap, 'VALIDATE_ATTENDANCE', "Korlap {$korlap->name} memvalidasi kehadiran extras {$user->name} di lokasi shooting", $absen, 0);
        }
    }

    /** Selfie dikirim lalu DITOLAK Korlap (beda dari selfie() yang cuma menunggu/divalidasi). */
    private function selfieDitolak(ProjectApplication $a, EventShootingDate $tgl, User $korlap): void
    {
        $user = $a->extras->user;
        $fotoPath = $this->png("absensi/{$a->id}/demo-selfie-ditolak.png", 'Selfie Demo');
        $absen = $a->attendances()->create([
            'event_shooting_date_id' => $tgl->id, 'status' => 'tidak_hadir', 'dicatat_oleh' => $user->id,
            'foto_path' => $fotoPath, 'catatan' => 'Foto buram dan lokasi tidak terlihat jelas, mohon kirim ulang.',
            'status_validasi' => 'tervalidasi', 'divalidasi_oleh' => $korlap->id, 'divalidasi_at' => now(),
        ]);
        $this->log($user, 'SUBMIT_SELFIE_ATTENDANCE', "Extras {$user->name} mengirimkan selfie absensi di lokasi shooting untuk proyek {$a->castingProject->nama_produksi}", $absen, 0);
        $this->log($korlap, 'REJECT_ATTENDANCE', "Korlap {$korlap->name} menolak validasi absensi extras {$user->name}", $absen, 0);
    }

    private function biaya(CastingProject $p, User $oleh, array $rows): void
    {
        foreach ($rows as [$label, $nominal, $hari]) {
            $p->expenses()->create(['label' => $label, 'nominal' => $nominal, 'tanggal' => today()->addDays($hari), 'created_by' => $oleh->id]);
            $this->log($oleh, 'ADD_EXPENSE', "{$oleh->name} mencatat beban operasional '{$label}' sebesar Rp ".number_format($nominal, 0, ',', '.')." untuk proyek '{$p->nama_produksi}'", $p, $hari);
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

    /** BM.1: $kirim = status email/WA seperti yang ditulis User::kabari(). */
    private function notif(User $user, string $judul, string $pesan, ?string $url, bool $dibaca, ?Carbon $at = null, array $kirim = []): void
    {
        $user->notifications()->create([
            'id' => Str::uuid()->toString(), 'type' => InAppNotification::class,
            'data' => (new InAppNotification($judul, $pesan, $url, $kirim))->toDatabase($user),
            'read_at' => $dibaca ? now() : null, 'created_at' => $at ?? now(),
        ]);
    }

    /**
     * Foto demo: coba unduh foto potret orang asli pravatar.cc (disimpan lokal).
     * Gagal/offline/unit-tests -> fallback ke SVG visual, seeding tidak boleh gagal.
     */
    private function fotoDemo(string $username, int $i, ?string $gender = null): string
    {
        // ponytail: test tanpa kirim keluar (DemoSeederTest::assertNothingSent) - jangan coba HTTP saat testing.
        if (app()->runningUnitTests()) {
            return $this->fotoDemoSvg($username, $i, $gender);
        }

        $path = "demo/foto/{$username}.jpg";
        if (Storage::disk('local')->exists($path)) {
            return $path;
        }

        $maleImgs = [3, 7, 8, 11, 12, 13, 14, 18, 33, 50, 51, 52, 53, 54, 55, 59, 60, 68];
        $femaleImgs = [1, 2, 5, 9, 10, 16, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 44, 45, 47, 48, 49];
        $pool = $gender === 'wanita' ? $femaleImgs : $maleImgs;
        $imgId = $pool[abs(crc32($username)) % count($pool)];

        try {
            $foto = Http::timeout(2)->get("https://i.pravatar.cc/300?img={$imgId}");
            if ($foto->successful()) {
                Storage::disk('local')->put($path, $foto->body());

                return $path;
            }
        } catch (\Throwable) {
            // fallback offline
        }

        return $this->fotoDemoSvg($username, $i, $gender);
    }

    private function fotoDemoSvg(string $username, int $i, ?string $gender = null): string
    {
        $h = ($i * 47) % 360;
        $path = "demo/foto/{$username}.svg";
        $initial = strtoupper(substr($username, 0, 2));
        $colorBg = $gender === 'wanita' ? 'hsl('.($h % 60 + 330).', 45%, 65%)' : 'hsl('.($h % 60 + 200).', 45%, 55%)';
        $colorDark = $gender === 'wanita' ? 'hsl('.($h % 60 + 330).', 35%, 25%)' : 'hsl('.($h % 60 + 200).', 35%, 20%)';

        Storage::disk('local')->put($path, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 520">'
            ."<defs><linearGradient id=\"g{$i}\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\"><stop offset=\"0\" stop-color=\"{$colorBg}\"/><stop offset=\"1\" stop-color=\"{$colorDark}\"/></linearGradient></defs>"
            ."<rect width=\"400\" height=\"520\" fill=\"url(#g{$i})\"/>"
            .'<circle cx="200" cy="195" r="80" fill="#FFFFFF" fill-opacity=".2"/>'
            .'<path d="M50 520c15-130 75-185 150-185s135 55 150 185z" fill="#FFFFFF" fill-opacity=".2"/>'
            ."<text x=\"200\" y=\"210\" font-family=\"sans-serif\" font-size=\"42\" font-weight=\"bold\" fill=\"#FFFFFF\" text-anchor=\"middle\">{$initial}</text>"
            .'</svg>');

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

    private function bersihkanDatabase(): void
    {
        Schema::disableForeignKeyConstraints();

        $tabel = [
            'activity_logs', 'admin_project_assignments', 'attendances', 'cancellations',
            'casting_project_class_extras_category', 'casting_project_classes', 'casting_projects',
            'client_reviews', 'contracts', 'event_shooting_dates', 'extras_category_extras_profile',
            'extras_profiles', 'fee_negotiations', 'field_notes', 'invoices', 'notifications',
            'payment_addons', 'payments', 'project_applications', 'project_attachments',
            'project_expenses', 'staff_payrolls', 'users',
        ];

        foreach ($tabel as $t) {
            DB::table($t)->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }
}
