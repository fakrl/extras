<?php

namespace Database\Seeders;

use App\Models\AdminProfile;
use App\Models\Attendance;
use App\Models\CastingProject;
use App\Models\CdProjectAssignment;
use App\Models\ExtrasCategory;
use App\Models\ExtrasProfile;
use App\Models\Invoice;
use App\Models\ProjectApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MasterOperationalSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ExtrasCategorySeeder::class);

        // 1. Akun 5 Roles Inti
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@jbtb.id'],
            [
                'name' => 'Super Admin JBTB',
                'username' => 'superadmin',
                'role' => 'super_admin',
                'password' => Hash::make('password'),
                'status' => 'aktif',
                'is_protected' => true,
            ]
        );

        $admin = User::updateOrCreate(
            ['email' => 'admin@jbtb.id'],
            [
                'name' => 'Admin Casting JBTB',
                'username' => 'admin_casting',
                'role' => 'admin',
                'password' => Hash::make('password'),
                'status' => 'aktif',
            ]
        );

        AdminProfile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'honor_nominal' => 5000000,
                'created_by' => $superAdmin->id,
            ]
        );

        $korlap = User::updateOrCreate(
            ['email' => 'korlap@jbtb.id'],
            [
                'name' => 'Koordinator Lapangan',
                'username' => 'korlap_lapangan',
                'role' => 'korlap',
                'password' => Hash::make('password'),
                'status' => 'aktif',
            ]
        );

        AdminProfile::updateOrCreate(
            ['user_id' => $korlap->id],
            [
                'honor_nominal' => 3500000,
                'created_by' => $superAdmin->id,
            ]
        );

        $clientScreenplay = User::updateOrCreate(
            ['email' => 'client@screenplay.id'],
            [
                'name' => 'Screenplay Films',
                'username' => 'screenplay_ph',
                'role' => 'client',
                'password' => Hash::make('password'),
                'status' => 'aktif',
            ]
        );

        $clientMd = User::updateOrCreate(
            ['email' => 'client@mdent.id'],
            [
                'name' => 'MD Entertainment',
                'username' => 'md_entertainment',
                'role' => 'client',
                'password' => Hash::make('password'),
                'status' => 'aktif',
            ]
        );

        // 2. Extras Data Riil & Profil Lengkap
        $categories = ExtrasCategory::all();

        $extrasData = [
            [
                'name' => 'Budi Santoso',
                'username' => 'budi_santoso',
                'email' => 'budi@extras.id',
                'nik' => '3274011203950001',
                'nama_asli' => 'Budi Santoso',
                'usia' => 29,
                'gender' => 'laki-laki',
                'tinggi_badan' => 172,
                'ukuran_baju' => 'L',
                'warna_kulit' => 'Sawo Matang',
                'pengalaman' => 'Pernah ikut 4 produksi FTV dan 2 serial web sebagai peran pendukung/warga.',
                'bahasa' => 'Indonesia, Jawa',
                'rate_card' => 350000,
                'rekening' => 'BCA 8271928371 a.n. Budi Santoso',
                'grade' => 'A',
                'cat' => 'Dewasa',
            ],
            [
                'name' => 'Siti Rahmawati',
                'username' => 'siti_rahma',
                'email' => 'siti@extras.id',
                'nik' => '3274015507990002',
                'nama_asli' => 'Siti Rahmawati',
                'usia' => 25,
                'gender' => 'perempuan',
                'tinggi_badan' => 163,
                'ukuran_baju' => 'M',
                'warna_kulit' => 'Kuning Langsat',
                'pengalaman' => 'Talent iklan komersial kosmetik, model katalog fashion hijab.',
                'bahasa' => 'Indonesia, Sunda',
                'rate_card' => 400000,
                'rekening' => 'Mandiri 1370019283741 a.n. Siti Rahmawati',
                'grade' => 'A',
                'cat' => 'Dewasa',
            ],
            [
                'name' => 'Reza Pratama',
                'username' => 'reza_actor',
                'email' => 'reza@extras.id',
                'nik' => '3274011808920003',
                'nama_asli' => 'Reza Pratama Putra',
                'usia' => 32,
                'gender' => 'laki-laki',
                'tinggi_badan' => 178,
                'ukuran_baju' => 'XL',
                'warna_kulit' => 'Sawo Matang',
                'pengalaman' => 'Pernah bermain di film laga sebagai peran gangster, stunt ringan, dan keamanan.',
                'bahasa' => 'Indonesia, Inggris',
                'rate_card' => 450000,
                'rekening' => 'BNI 0928374615 a.n. Reza Pratama',
                'grade' => 'B',
                'cat' => 'Dewasa',
            ],
            [
                'name' => 'Dewi Lestari',
                'username' => 'dewi_talent',
                'email' => 'dewi@extras.id',
                'nik' => '3274016209800004',
                'nama_asli' => 'Dewi Lestari Ningsih',
                'usia' => 44,
                'gender' => 'perempuan',
                'tinggi_badan' => 158,
                'ukuran_baju' => 'L',
                'warna_kulit' => 'Sawo Matang',
                'pengalaman' => 'Berpengalaman memerankan karakter ibu rumah tangga, pedagang pasar, dan tamu undangan.',
                'bahasa' => 'Indonesia, Jawa',
                'rate_card' => 350000,
                'rekening' => 'BCA 5271829304 a.n. Dewi Lestari',
                'grade' => 'B',
                'cat' => 'Orang Tua',
            ],
            [
                'name' => 'Kevin Wijaya',
                'username' => 'kevin_w',
                'email' => 'kevin@extras.id',
                'nik' => '3274012010040005',
                'nama_asli' => 'Kevin Wijaya Saputra',
                'usia' => 20,
                'gender' => 'laki-laki',
                'tinggi_badan' => 175,
                'ukuran_baju' => 'M',
                'warna_kulit' => 'Putih',
                'pengalaman' => 'Aktif teater kampus, peran mahasiswa dan anak muda nongkrong.',
                'bahasa' => 'Indonesia, Mandarin, Inggris',
                'rate_card' => 300000,
                'rekening' => 'BCA 1928374650 a.n. Kevin Wijaya',
                'grade' => 'C',
                'cat' => 'Chinese/Tionghoa',
            ],
            [
                'name' => 'Maya Indah',
                'username' => 'maya_indah',
                'email' => 'maya@extras.id',
                'nik' => '3274014402980006',
                'nama_asli' => 'Maya Indah Permata',
                'usia' => 26,
                'gender' => 'perempuan',
                'tinggi_badan' => 166,
                'ukuran_baju' => 'S',
                'warna_kulit' => 'Kuning Langsat',
                'pengalaman' => 'Talent video klip musik dan figuran dialog pendek di webseries.',
                'bahasa' => 'Indonesia, Inggris',
                'rate_card' => 400000,
                'rekening' => 'BSI 7182930415 a.n. Maya Indah',
                'grade' => 'A',
                'cat' => 'Dewasa',
            ],
        ];

        $extrasProfiles = [];

        foreach ($extrasData as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'role' => 'extras',
                    'password' => Hash::make('password'),
                    'status' => 'aktif',
                ]
            );

            $profile = ExtrasProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nik' => $data['nik'],
                    'nama_asli' => $data['nama_asli'],
                    'usia' => $data['usia'],
                    'gender' => $data['gender'],
                    'tinggi_badan' => $data['tinggi_badan'],
                    'ukuran_baju' => $data['ukuran_baju'],
                    'warna_kulit' => $data['warna_kulit'],
                    'pengalaman' => $data['pengalaman'],
                    'bahasa' => $data['bahasa'],
                    'rate_card' => $data['rate_card'],
                    'rekening' => $data['rekening'],
                    'grade_saat_ini' => $data['grade'],
                    'grade_diberikan_at' => now()->subMonth(),
                ]
            );

            $catModel = $categories->firstWhere('nama', $data['cat']);
            if ($catModel) {
                $profile->categories()->syncWithoutDetaching([$catModel->id]);
            }

            $extrasProfiles[$data['username']] = $profile;
        }

        // Akun Mangkrak dummy (terdaftar 40 hari lalu, profil kosong, 0 pendaftaran) untuk demonstrasi fitur Prune
        $mangkrakUser = User::updateOrCreate(
            ['email' => 'mangkrak@demo.test'],
            [
                'name' => 'Akun Mangkrak Demo',
                'username' => 'mangkrak_demo',
                'role' => 'extras',
                'password' => Hash::make('password'),
                'status' => 'aktif',
                'created_at' => now()->subDays(40),
            ]
        );
        ExtrasProfile::updateOrCreate(
            ['user_id' => $mangkrakUser->id],
            [
                'nik' => null,
                'nama_asli' => null,
                'created_at' => now()->subDays(40),
            ]
        );

        // 3. Proyek 1: Terbuka & Aktif
        $project1 = CastingProject::updateOrCreate(
            ['nama_produksi' => 'Film Layar Lebar: Pengantin Iblis'],
            [
                'admin_id' => $admin->id,
                'client_ph' => 'MD Entertainment',
                'deadline' => today()->addDays(5),
                'kuota' => 15,
                'is_urgent' => false,
                'status' => 'dibuka',
                'client_request_status' => 'disetujui',
                'share_token' => Str::random(32),
                'wa_group_link' => 'https://chat.whatsapp.com/demo-pengantin-iblis',
            ]
        );

        $project1->shootingDates()->delete();
        $tgl1 = $project1->shootingDates()->create(['tanggal' => today()->addDays(7), 'lokasi' => 'Desa Wisata Pangalengan, Kab. Bandung', 'jam_mulai' => '06:00:00']);
        $project1->shootingDates()->create(['tanggal' => today()->addDays(8), 'lokasi' => 'Hutan Pinus Rahong, Pangalengan', 'jam_mulai' => '07:00:00']);

        $project1->classes()->delete();
        $class1A = $project1->classes()->create([
            'nama_kelas' => 'Warga Desa Adat (Pria 25-45 th)',
            'karakter' => 'Warga Pengiring Adat',
            'kuota_kelas' => 8,
            'budget_client' => 350000,
            'jam_callsheet' => '07:00',
            'jam_callingan' => '06:00',
            'keterangan_scene' => 'Scene 15-18 Upacara Sakral Desa, kostum lurik & ikat kepala',
            'tipe_continuity' => 'continuity',
        ]);
        $class1B = $project1->classes()->create([
            'nama_kelas' => 'Kerabat Mempelai (Wanita 20-35 th)',
            'karakter' => 'Dayang / Kerabat Mempelai',
            'kuota_kelas' => 7,
            'budget_client' => 350000,
            'jam_callsheet' => '08:00',
            'jam_callingan' => '07:00',
            'keterangan_scene' => 'Scene 20 Prosesi Ijab, kebaya tradisional warna cerah',
            'tipe_continuity' => 'free',
        ]);

        CdProjectAssignment::updateOrCreate([
            'casting_project_id' => $project1->id,
            'cd_user_id' => $clientMd->id,
        ]);

        ProjectApplication::updateOrCreate(
            ['casting_project_id' => $project1->id, 'extras_id' => $extrasProfiles['budi_santoso']->id],
            [
                'casting_project_class_id' => $class1A->id,
                'status_partisipasi' => 'diajukan',
                'karakter_override' => 'Warga Pengiring Adat 1',
                'jam_callingan_override' => '06:00',
                'scene_override' => 'Scene 15-18 Upacara',
            ]
        );

        ProjectApplication::updateOrCreate(
            ['casting_project_id' => $project1->id, 'extras_id' => $extrasProfiles['siti_rahma']->id],
            [
                'casting_project_class_id' => $class1B->id,
                'status_partisipasi' => 'direview_admin',
                'karakter_override' => 'Kerabat Mempelai',
                'jam_callingan_override' => '07:00',
                'scene_override' => 'Scene 20 Ijab',
            ]
        );

        // 4. Proyek 2: Urgent H-3
        $project2 = CastingProject::updateOrCreate(
            ['nama_produksi' => 'Iklan TVC Kopi Kenangan Mantan'],
            [
                'admin_id' => $admin->id,
                'client_ph' => 'Cerita Creative Films',
                'deadline' => today()->addDays(1),
                'kuota' => 6,
                'is_urgent' => true,
                'status' => 'dibuka',
                'client_request_status' => 'disetujui',
                'share_token' => Str::random(32),
                'wa_group_link' => 'https://chat.whatsapp.com/demo-kopi-kenangan',
            ]
        );

        $project2->shootingDates()->delete();
        $tgl2 = $project2->shootingDates()->create([
            'tanggal' => today()->addDays(2),
            'lokasi' => 'Kopi Kenangan Senopati, Jakarta Selatan',
            'jam_mulai' => '06:30:00',
        ]);

        $project2->classes()->delete();
        $class2 = $project2->classes()->create([
            'nama_kelas' => 'Pengunjung Kafe Muda-mudi (20-30 th)',
            'karakter' => 'Pasangan Meja Pojok',
            'kuota_kelas' => 6,
            'budget_client' => 450000,
            'jam_callsheet' => '07:30',
            'jam_callingan' => '06:30',
            'keterangan_scene' => 'Scene 1-4 Ngopi santai casual aesthetic smart-casual',
            'tipe_continuity' => 'free',
        ]);

        ProjectApplication::updateOrCreate(
            ['casting_project_id' => $project2->id, 'extras_id' => $extrasProfiles['reza_actor']->id],
            [
                'casting_project_class_id' => $class2->id,
                'status_partisipasi' => 'deal',
                'karakter_override' => 'Pengunjung Kafe Cowok',
                'jam_callingan_override' => '06:30',
            ]
        );

        ProjectApplication::updateOrCreate(
            ['casting_project_id' => $project2->id, 'extras_id' => $extrasProfiles['maya_indah']->id],
            [
                'casting_project_class_id' => $class2->id,
                'status_partisipasi' => 'lolos',
                'karakter_override' => 'Pengunjung Kafe Cewek',
                'jam_callingan_override' => '06:30',
            ]
        );

        // 5. Proyek 3: Pengajuan dari Client (Menunggu ACC Super Admin)
        CastingProject::updateOrCreate(
            ['nama_produksi' => 'Webseries: Jejak Rahasia Season 2'],
            [
                'client_ph' => 'Screenplay Productions',
                'diajukan_oleh_client_id' => $clientScreenplay->id,
                'deadline' => today()->addDays(14),
                'kuota' => 10,
                'is_urgent' => false,
                'status' => 'ditutup',
                'client_request_status' => 'menunggu_acc',
                'brief_catatan' => 'Membutuhkan 10 extras bertubuh tegap dan rapi untuk adegan kantor kepolisian & forensik. Estimasi shooting akhir bulan.',
                'share_token' => Str::random(32),
            ]
        );

        // 6. Proyek 4: Berjalan & Absensi Lapangan On-Site Hari Ini
        $project4 = CastingProject::updateOrCreate(
            ['nama_produksi' => 'Film Drama: Janji Jiwa 2'],
            [
                'admin_id' => $admin->id,
                'client_ph' => 'Screenplay Productions',
                'deadline' => today()->subDays(2),
                'kuota' => 4,
                'is_urgent' => false,
                'status' => 'dibuka',
                'client_request_status' => 'disetujui',
                'share_token' => Str::random(32),
                'wa_group_link' => 'https://chat.whatsapp.com/demo-janji-jiwa',
            ]
        );

        $project4->shootingDates()->delete();
        $tglShootingHariIni = $project4->shootingDates()->create([
            'tanggal' => today(),
            'lokasi' => 'Taman Suropati & Stasiun Cikini, Menteng',
            'jam_mulai' => '07:00:00',
            'jam_selesai' => '17:00:00',
        ]);

        $project4->classes()->delete();
        $class4 = $project4->classes()->create([
            'nama_kelas' => 'Pejalan Kaki & Warga Taman',
            'karakter' => 'Warga Taman Santai',
            'kuota_kelas' => 4,
            'budget_client' => 350000,
            'jam_callsheet' => '08:00',
            'jam_callingan' => '07:00',
            'keterangan_scene' => 'Scene 32-35 Pertemuan Tokoh Utama di Taman',
            'tipe_continuity' => 'free',
        ]);

        CdProjectAssignment::updateOrCreate([
            'casting_project_id' => $project4->id,
            'cd_user_id' => $clientScreenplay->id,
        ]);

        // Budi Santoso: Lolos -> Kontrak Ditandatangani -> Absen Hari Ini (Tervalidasi Korlap)
        $appBudi = ProjectApplication::updateOrCreate(
            ['casting_project_id' => $project4->id, 'extras_id' => $extrasProfiles['budi_santoso']->id],
            [
                'casting_project_class_id' => $class4->id,
                'status_partisipasi' => 'kontrak_ditandatangani',
                'karakter_override' => 'Pejalan Kaki 1',
                'jam_callingan_override' => '07:00',
                'scene_override' => 'Scene 32 Taman',
            ]
        );
        $appBudi->contract()->updateOrCreate(
            ['project_application_id' => $appBudi->id],
            [
                'signed_at' => now()->subDay(),
                'ttd_admin_signature_path' => 'contracts/signatures/demo-admin.png',
                'ttd_extras_signature_path' => 'contracts/signatures/demo-budi.png',
            ]
        );
        Attendance::updateOrCreate(
            ['project_application_id' => $appBudi->id, 'event_shooting_date_id' => $tglShootingHariIni->id],
            [
                'status' => 'hadir',
                'dicatat_oleh' => $korlap->id,
                'status_validasi' => 'tervalidasi',
                'divalidasi_oleh' => $korlap->id,
                'divalidasi_at' => now(),
                'catatan' => 'Hadir tepat waktu 06:45 WIB di lokasi.',
            ]
        );

        // Siti Rahmawati: Kontrak Ditandatangani -> Absen Selfie Hybrid (Menunggu Validasi Korlap)
        $appSiti = ProjectApplication::updateOrCreate(
            ['casting_project_id' => $project4->id, 'extras_id' => $extrasProfiles['siti_rahma']->id],
            [
                'casting_project_class_id' => $class4->id,
                'status_partisipasi' => 'kontrak_ditandatangani',
                'karakter_override' => 'Pejalan Kaki 2',
                'jam_callingan_override' => '07:00',
                'scene_override' => 'Scene 32 Taman',
            ]
        );
        $appSiti->contract()->updateOrCreate(
            ['project_application_id' => $appSiti->id],
            [
                'signed_at' => now()->subDay(),
                'ttd_admin_signature_path' => 'contracts/signatures/demo-admin.png',
                'ttd_extras_signature_path' => 'contracts/signatures/demo-siti.png',
            ]
        );
        Attendance::updateOrCreate(
            ['project_application_id' => $appSiti->id, 'event_shooting_date_id' => $tglShootingHariIni->id],
            [
                'status' => 'hadir',
                'dicatat_oleh' => $extrasProfiles['siti_rahma']->user_id,
                'status_validasi' => 'menunggu',
                'catatan' => 'Selfie Absensi Extras (Hybrid On-Site)',
            ]
        );

        // Invoice Project 4 (Signed by Admin & Client)
        Invoice::updateOrCreate(
            ['casting_project_id' => $project4->id],
            [
                'ttd_admin_signature_path' => 'invoices/signatures/demo-admin.png',
                'ttd_cd_signature_path' => 'invoices/signatures/demo-cd.png',
                'template_type' => 'default',
            ]
        );
    }
}
