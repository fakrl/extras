<?php

use App\Http\Controllers\Admin\AkunClientController;
use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\CastingProjectController as AdminCastingProjectController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FeeNegotiationController as AdminFeeNegotiationController;
use App\Http\Controllers\Admin\KeuanganProyekController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WorkHistoryController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Client\DashboardController as ClientDashboardController;
use App\Http\Controllers\Client\JadwalController as ClientJadwalController;
use App\Http\Controllers\Client\ProfilController as ClientProfilController;
use App\Http\Controllers\Client\ProjectRequestController;
use App\Http\Controllers\Client\ReviewController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\Extras\AttendanceSelfieController;
use App\Http\Controllers\Extras\CastingProjectController as ExtrasCastingProjectController;
use App\Http\Controllers\Extras\DashboardController as ExtrasDashboardController;
use App\Http\Controllers\Extras\FeeNegotiationController as ExtrasFeeNegotiationController;
use App\Http\Controllers\Extras\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProjectAttachmentController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\PublicExtrasProfileController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\AdminManagementController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\GlobalSearchController;
use App\Http\Controllers\SuperAdmin\ModeRoleController;
use App\Http\Controllers\SuperAdmin\MonitoringController;
use App\Http\Controllers\SuperAdmin\ProjectAssignmentController;
use App\Http\Controllers\UbahPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// RF-55: homepage compro publik. Bukan auth gate: tetap tampil apa pun
// status login user, konten CTA-nya saja yang beda (lihat HomeController).
Route::get('/', [HomeController::class, 'index'])->name('home');

// RF-56: link publik pendaftaran per event, dibagikan Admin lewat WA.
// Sengaja di luar grup middleware auth/guest - guest maupun user login
// (extras/admin/Client) manapun boleh buka, otorisasi granular di controller.
Route::get('/event/{token}', [PublicEventController::class, 'show'])->name('public.event.show');

// Bagian S: halaman profil publik Extras via share link. Tidak perlu auth.
// Tembok visibilitas ditegakkan di controller & view (tanpa nik/nama_asli/rekening/rate_card/tautan_tambahan).
Route::get('/p/extras/{token}', [PublicExtrasProfileController::class, 'show'])->name('public.extras.profile');
Route::get('/p/extras/{token}/foto', [PublicExtrasProfileController::class, 'foto'])->name('public.extras.foto');
Route::get('/p/extras/{token}/video', [PublicExtrasProfileController::class, 'video'])->name('public.extras.video');
Route::get('/p/extras/{token}/foto-tambahan/{slot}', [PublicExtrasProfileController::class, 'fotoTambahan'])->whereNumber('slot')->name('public.extras.foto-tambahan');

// Pintu masuk universal setelah login (dipakai mis. link "kembali ke
// dashboard" generik) - lempar ke dashboard sesuai role via
// User::dashboardUrl(), satu-satunya sumber kebenaran mapping role→URL.
Route::middleware('auth')->get('/dashboard', function () {
    return redirect(auth()->user()->dashboardUrl());
})->name('dashboard');

// ==================== AUTH ====================

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');

    // RF-01: registrasi Extras, publik.
    Route::get('/register', [RegisterController::class, 'showExtras'])->name('register');
    Route::post('/register', [RegisterController::class, 'registerExtras'])->middleware('throttle:5,1');

    // BD.1.5: registrasi publik Client ditutup, akun dibuat Super Admin.
    Route::match(['get', 'post'], '/register/casting-director', fn () => redirect()->route('login')
        ->with('status', 'Akun Client dibuatkan oleh tim JBTB. Hubungi kami untuk mendapatkan akses.'));

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');

    Route::get('/auth/google/lanjut', [GoogleController::class, 'lanjut'])->name('google.lanjut');
    Route::post('/auth/google/lanjut', [GoogleController::class, 'daftar'])->middleware('throttle:5,1')->name('google.daftar');
});

// BO.2: login Google (mode=login) & hubungkan (mode=hubungkan, perlu login). 404 kalau GOOGLE_CLIENT_ID kosong.
Route::middleware('throttle:10,1')->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback');
});
Route::post('/auth/google/putus', [GoogleController::class, 'putus'])->middleware('auth')->name('google.putus');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ubah-password', [UbahPasswordController::class, 'edit'])->name('ubah-password');
    Route::post('/ubah-password', [UbahPasswordController::class, 'update'])->name('ubah-password.update');
    Route::post('/validate-current-password', [UbahPasswordController::class, 'validateCurrentPassword'])->name('ubah-password.validate');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::get('/tag/cari', [TagController::class, 'cari'])->name('tag.cari');
});

// ==================== EXTRAS ====================

Route::middleware(['auth', 'role:extras'])->prefix('extras')->group(function () {
    Route::get('/dashboard', [ExtrasDashboardController::class, 'index'])->name('extras.dashboard');

    Route::get('/profil', [ProfileController::class, 'show'])->name('extras.profile.show');
    Route::get('/profil/lengkapi', [ProfileController::class, 'edit'])->name('extras.profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('extras.profile.update');
    Route::post('/profil/foto', [ProfileController::class, 'uploadFoto'])->name('extras.profile.foto');
    Route::post('/profil/foto/ajax', [ProfileController::class, 'uploadFotoJson'])->name('extras.profile.foto.ajax');
    Route::post('/profil/video', [ProfileController::class, 'uploadVideo'])->name('extras.profile.video');
    Route::post('/profil/video/ajax', [ProfileController::class, 'uploadVideoJson'])->name('extras.profile.video.ajax');
    Route::post('/profil/foto-tambahan/{slot}', [ProfileController::class, 'uploadFotoTambahan'])
        ->whereNumber('slot')->name('extras.profile.foto-tambahan');
    Route::post('/profil/foto-tambahan/{slot}/ajax', [ProfileController::class, 'uploadFotoTambahanJson'])
        ->whereNumber('slot')->name('extras.profile.foto-tambahan.ajax');
    Route::delete('/profil/foto-tambahan/{slot}', [ProfileController::class, 'hapusFotoTambahan'])
        ->whereNumber('slot')->name('extras.profile.foto-tambahan.hapus');

    Route::get('/lowongan', [ExtrasCastingProjectController::class, 'index'])->name('extras.projects.index');
    Route::get('/lowongan/{castingProject}', [ExtrasCastingProjectController::class, 'show'])->name('extras.projects.show');
    Route::post('/lowongan/{castingProject}/daftar', [ExtrasCastingProjectController::class, 'apply'])
        ->middleware('throttle:5,1')->name('extras.projects.apply');

    Route::get('/nego/{application}', [ExtrasFeeNegotiationController::class, 'show'])->name('extras.negotiations.show');
    Route::post('/nego/{application}/terima', [ExtrasFeeNegotiationController::class, 'terima'])->name('extras.negotiations.terima');
    Route::post('/nego/{application}/counter', [ExtrasFeeNegotiationController::class, 'counter'])->name('extras.negotiations.counter');
    Route::post('/nego/{application}/batalkan', [ExtrasFeeNegotiationController::class, 'batalkan'])->name('extras.negotiations.batalkan');

    Route::get('/kontrak/{application}/lengkapi-ktp', [ProfileController::class, 'lengkapiKtp'])->name('extras.kontrak.lengkapi-ktp');
    Route::post('/kontrak/{application}/lengkapi-ktp', [ProfileController::class, 'simpanKtp'])
        ->middleware('throttle:5,1')->name('extras.kontrak.simpan-ktp');

    Route::post('/absensi-selfie/{application}', [AttendanceSelfieController::class, 'store'])->name('extras.absensi.selfie');
});

// ==================== ADMIN & KORLAP ====================

Route::middleware(['auth', 'role:admin,korlap,super_admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // RF-43/RF-44: Riwayat Kerja & Status Gaji
        Route::get('/riwayat-kerja', [WorkHistoryController::class, 'index'])->name('admin.work-history');

        // Operasional Proyek: Admin & Super Admin (Godmode)
        Route::middleware('role:admin,super_admin')->group(function () {
            Route::get('/akun/extras', [UserManagementController::class, 'extras'])->name('admin.akun.extras');
            Route::get('/akun/extras/export', [UserManagementController::class, 'export'])->name('admin.akun.extras.export');
            Route::get('/akun/client', [AkunClientController::class, 'index'])->name('admin.akun.client');
            Route::get('/users', [UserManagementController::class, 'keExtras'])->name('admin.users.index');
            Route::get('/recap', [UserManagementController::class, 'keExtras'])->name('admin.recap.index');
            Route::get('/recap/export', [UserManagementController::class, 'keExtras'])->name('admin.recap.export');
            Route::get('/extras/{user}/profil', [UserManagementController::class, 'showProfile'])->name('admin.extras.profil');
            Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
                ->name('admin.users.toggle-status');
            Route::patch('/users/{user}/kategori', [UserManagementController::class, 'updateKategori'])
                ->name('admin.users.kategori');
            Route::redirect('/tag', '/admin/akun/extras')->name('admin.tags.index');
            Route::delete('/tag/{extrasCategory}', [TagController::class, 'destroy'])->name('admin.tags.destroy');
            Route::patch('/tag/{extrasCategory}', [TagController::class, 'update'])->name('admin.tags.update');
            Route::post('/tag/{extrasCategory}/gabung', [TagController::class, 'gabung'])->name('admin.tags.gabung');
            Route::patch('/extras/{user}/beranda', [UserManagementController::class, 'toggleBeranda'])
                ->name('admin.extras.beranda');
            Route::patch('/extras/{user}/favorit', [UserManagementController::class, 'toggleFavorit'])
                ->name('admin.extras.favorit');
            Route::post('/users/prune-abandoned', [UserManagementController::class, 'pruneAbandoned'])
                ->name('admin.users.prune');

            Route::get('/projects', [AdminCastingProjectController::class, 'index'])->name('admin.projects.index');
            Route::get('/projects/create', [AdminCastingProjectController::class, 'create'])->name('admin.projects.create');
            Route::post('/projects', [AdminCastingProjectController::class, 'store'])->name('admin.projects.store');
            Route::get('/projects/{castingProject}', [AdminCastingProjectController::class, 'show'])->name('admin.projects.show');
            Route::get('/projects/{castingProject}/edit', [AdminCastingProjectController::class, 'edit'])->name('admin.projects.edit');
            Route::patch('/projects/{castingProject}', [AdminCastingProjectController::class, 'update'])->name('admin.projects.update');
            Route::patch('/projects/{castingProject}/toggle-status', [AdminCastingProjectController::class, 'toggleStatus'])
                ->name('admin.projects.toggle-status');
            Route::patch('/projects/{castingProject}/portofolio', [AdminCastingProjectController::class, 'updatePortofolio'])
                ->name('admin.projects.portofolio');
            Route::get('/projects/{castingProject}/applicants', [AdminCastingProjectController::class, 'showApplicants'])
                ->name('admin.projects.applicants');
            Route::post('/projects/{castingProject}/applicants/bulk', [ApplicantController::class, 'bulk'])
                ->name('admin.projects.applicants.bulk');

            Route::patch('/applications/{application}/grade', [ApplicantController::class, 'setGrade'])
                ->name('admin.applications.grade');

            Route::patch('/applications/{application}/reject', [ApplicantController::class, 'reject'])
                ->name('admin.applications.reject');

            Route::post('/applications/{application}/apresiasi', [ApplicantController::class, 'toggleApresiasi'])
                ->name('admin.applications.apresiasi');

            Route::patch('/applications/{application}/breakdown', [ApplicantController::class, 'updateBreakdown'])
                ->name('admin.applications.breakdown');

            Route::get('/applications/{application}/nego', [AdminFeeNegotiationController::class, 'show'])
                ->name('admin.negotiations.show');
            Route::post('/applications/{application}/nego/ajukan', [AdminFeeNegotiationController::class, 'ajukanAwal'])
                ->name('admin.negotiations.ajukan');
            Route::post('/applications/{application}/nego/counter', [AdminFeeNegotiationController::class, 'counter'])
                ->name('admin.negotiations.counter');
            Route::post('/applications/{application}/nego/terima', [AdminFeeNegotiationController::class, 'terima'])
                ->name('admin.negotiations.terima');
            Route::post('/applications/{application}/nego/tolak', [AdminFeeNegotiationController::class, 'tolak'])
                ->name('admin.negotiations.tolak');
            Route::post('/applications/{application}/ajukan-ke-client', [AdminFeeNegotiationController::class, 'ajukanKeClient'])
                ->name('admin.negotiations.ajukan-ke-client');
            Route::post('/applications/{application}/batalkan', [AdminFeeNegotiationController::class, 'batalkan'])
                ->name('admin.negotiations.batalkan');
        });

        // RF-35: Korlap, Admin, dan Super Admin boleh nulis catatan lapangan.
        Route::middleware('role:admin,korlap,super_admin')->group(function () {
            Route::post('/applications/{application}/catatan', [ApplicantController::class, 'tambahCatatan'])
                ->name('admin.applications.catatan');
        });

        // Absensi Extras - Korlap, Admin, dan Super Admin (Godmode)
        Route::middleware('role:korlap,admin,super_admin')->group(function () {
            Route::get('/absensi', [AttendanceController::class, 'index'])->name('admin.attendance.index');
            Route::post('/applications/{application}/absen', [AttendanceController::class, 'store'])
                ->name('admin.attendance.store');
            Route::post('/absensi/{attendance}/validasi', [AttendanceController::class, 'validasi'])
                ->name('admin.absensi.validasi');
            Route::post('/absensi/{attendance}/tolak', [AttendanceController::class, 'tolakValidasi'])
                ->name('admin.absensi.tolak');
            Route::get('/media/absensi/{attendance}', [AttendanceController::class, 'fotoStream'])
                ->name('admin.absensi.foto');
        });
    });

// RF-30 & SPEC AV/BD.2: Keuangan proyek - Admin & Super Admin. rekap-margin lama = redirect.
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->group(function () {
    Route::get('/rekap-margin', [KeuanganProyekController::class, 'rekapMargin'])->name('admin.recap-margin');
    Route::patch('/payrolls/{staffPayroll}/tandai-dibayar', [KeuanganProyekController::class, 'tandaiDibayar'])
        ->name('admin.payrolls.tandai-dibayar');
    Route::patch('/projects/{castingProject}/invoice-lunas', [KeuanganProyekController::class, 'tandaiLunas'])
        ->name('admin.projects.invoice-lunas');
    Route::post('/projects/{castingProject}/biaya', [KeuanganProyekController::class, 'storeExpense'])
        ->name('admin.projects.expenses.store');
    Route::delete('/biaya/{projectExpense}', [KeuanganProyekController::class, 'destroyExpense'])
        ->name('admin.expenses.destroy');
});

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/rekap-margin', [KeuanganProyekController::class, 'rekapMargin'])->name('super-admin.recap-margin');
    Route::patch('/payrolls/{staffPayroll}/tandai-dibayar', [KeuanganProyekController::class, 'tandaiDibayar'])
        ->name('super-admin.payrolls.tandai-dibayar');
});

// ==================== SUPER ADMIN ====================

Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('super-admin.dashboard');

    Route::get('/akun', [AdminManagementController::class, 'akun'])->name('super-admin.akun.index');
    Route::get('/monitoring', [AdminManagementController::class, 'keAkun'])->name('super-admin.monitoring');
    Route::get('/monitoring/admin', [MonitoringController::class, 'admin'])->name('super-admin.monitoring.admin');
    Route::get('/monitoring/korlap', [MonitoringController::class, 'korlap'])->name('super-admin.monitoring.korlap');

    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('super-admin.attendance.index');

    Route::get('/admins', [AdminManagementController::class, 'keAkun'])->name('super-admin.admins.index');
    Route::post('/admins', [AdminManagementController::class, 'store'])->name('super-admin.admins.store');
    Route::post('/admins/bulk-action', [AdminManagementController::class, 'bulkAction'])->name('super-admin.admins.bulk-action');
    Route::post('/admins/{user}/reset-password', [AdminManagementController::class, 'resetPassword'])->name('super-admin.admins.reset-password');
    Route::get('/admins/{user}', [AdminManagementController::class, 'show'])->name('super-admin.admins.show');
    Route::patch('/admins/{user}', [AdminManagementController::class, 'update'])->name('super-admin.admins.update');
    Route::patch('/admins/{user}/honor', [AdminManagementController::class, 'updateHonor'])->name('super-admin.admins.honor');
    Route::patch('/admins/{user}/toggle-status', [AdminManagementController::class, 'toggleStatus'])->name('super-admin.admins.toggle-status');
    Route::patch('/admins/{user}/kategori', [UserManagementController::class, 'updateKategori'])->name('super-admin.admins.kategori');
    Route::patch('/admins/{user}/restore', [AdminManagementController::class, 'restore'])->name('super-admin.admins.restore');
    Route::delete('/admins/{user}', [AdminManagementController::class, 'destroy'])->name('super-admin.admins.destroy');

    Route::redirect('/casting-directors', '/super-admin/akun?role=client');
    Route::post('/casting-directors', [AdminManagementController::class, 'storeClient'])->name('super-admin.clients.store');

    Route::post('/projects/{castingProject}/assign', [ProjectAssignmentController::class, 'assign'])
        ->name('super-admin.assignments.assign');
    Route::post('/assignments/{assignment}/complete', [ProjectAssignmentController::class, 'markComplete'])
        ->name('super-admin.assignments.complete');
    Route::post('/payrolls/{payroll}/addon', [ProjectAssignmentController::class, 'addAddon'])
        ->name('super-admin.payrolls.addon');

    // Modul 2: Super Admin ACC / Tolak Permintaan Proyek dari Client
    Route::match(['post', 'patch'], '/projects/{castingProject}/acc', [SuperAdminDashboardController::class, 'accProject'])
        ->name('super-admin.projects.acc');
    Route::match(['post', 'patch'], '/projects/{castingProject}/reject', [SuperAdminDashboardController::class, 'rejectProject'])
        ->name('super-admin.projects.reject');

    // Audit Trail: Log Aktivitas Seluruh Role
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->name('super-admin.activity-logs');

    Route::get('/search', [GlobalSearchController::class, 'search'])->name('super-admin.search');

    // SPEC BD.6: Monitoring sebagai role. ViewAs middleware (grup web) yang menegakkan read-only.
    Route::get('/sebagai/{mode}', [ModeRoleController::class, 'pilih'])->name('super-admin.mode.pilih');
    Route::post('/sebagai', [ModeRoleController::class, 'mulai'])->name('super-admin.mode.mulai');
    Route::post('/sebagai/keluar', [ModeRoleController::class, 'keluar'])->name('super-admin.mode.keluar');
});

// ==================== CLIENT ====================

// BM.3: URL lama /cd/... (bookmark & link notifikasi lama) dialihkan permanen ke /client/...
Route::get('/cd/{any?}', fn (Request $request, ?string $any = null) => redirect()->to(
    '/client/'.$any.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301
))->where('any', '.*');

Route::middleware(['auth', 'role:client'])->prefix('client')->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
    Route::get('/profil', [ClientProfilController::class, 'edit'])->name('client.profil');
    Route::put('/profil', [ClientProfilController::class, 'update'])->name('client.profil.update');

    // Modul 2: Pengajuan brief permintaan proyek oleh Client (Pintu 1)
    Route::get('/projects/request', [ProjectRequestController::class, 'create'])->name('client.projects.request');
    Route::post('/projects/request', [ProjectRequestController::class, 'store'])->name('client.projects.request.store');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('client.reviews.index');
    Route::post('/reviews', [ReviewController::class, 'review'])->name('client.reviews.review');
    Route::get('/reviews/{castingProject}', [ReviewController::class, 'show'])->name('client.reviews.show');
    Route::get('/extras/{user}/profil', [ReviewController::class, 'profil'])->name('client.extras.profil');
    Route::get('/reviews/{castingProject}/export/xlsx', [ReviewController::class, 'exportRiwayatXlsx'])->name('client.riwayat.export.xlsx');
    Route::get('/reviews/{castingProject}/export/pdf', [ReviewController::class, 'exportRiwayatPdf'])->name('client.riwayat.export.pdf');

    Route::get('/jadwal', [ClientJadwalController::class, 'index'])->name('client.jadwal.index');
    Route::get('/jadwal/{project}', [ClientJadwalController::class, 'show'])->name('client.jadwal.show');
    Route::post('/jadwal/{project}', [ClientJadwalController::class, 'store'])->name('client.jadwal.store');

    Route::get('/absensi/{attendance}/foto', [AttendanceController::class, 'clientFotoStream'])->name('client.absensi.foto');
});

// ==================== KONTRAK (lintas role: Admin Default & Extras) ====================
// RF-25/26/27: satu resource yang diakses dua pihak berbeda, otorisasi
// granular ditegakkan di dalam ContractController, bukan lewat role middleware.

Route::middleware('auth')->prefix('kontrak')->group(function () {
    Route::get('/{application}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/{application}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
    Route::get('/{application}/pdf', [ContractController::class, 'downloadPdf'])->name('contracts.download-pdf');
});

// ==================== INVOICE (lintas role: Admin Default & Client) ====================

Route::middleware('auth')->prefix('invoice')->group(function () {
    Route::get('/', [InvoiceController::class, 'indexClient'])->name('invoices.index-client');
    Route::get('/{castingProject}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/{castingProject}/sign', [InvoiceController::class, 'sign'])->name('invoices.sign');
    Route::post('/{castingProject}/custom-doc', [InvoiceController::class, 'uploadCustomDoc'])->name('invoices.upload-custom');
    Route::get('/{castingProject}/custom-doc', [InvoiceController::class, 'downloadCustomDoc'])->name('invoices.download-custom');
    Route::get('/{castingProject}/download-pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.download-pdf');
});

// SPEC BD.9: lampiran proyek - SA, Admin, Client proyek. Otorisasi di ProjectAttachmentController::bolehAkses().
Route::middleware('auth')->group(function () {
    Route::post('/proyek/{castingProject}/lampiran', [ProjectAttachmentController::class, 'store'])->name('project-attachments.store');
    Route::get('/lampiran/{projectAttachment}', [ProjectAttachmentController::class, 'download'])->name('project-attachments.download');
    Route::delete('/lampiran/{projectAttachment}', [ProjectAttachmentController::class, 'destroy'])->name('project-attachments.destroy');
});

// ==================== PEMBAYARAN EXTRAS (lintas role: Admin Default & Extras) ====================

Route::middleware('auth')->prefix('pembayaran')->group(function () {
    Route::get('/{application}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/{application}/transfer', [PaymentController::class, 'tandaiTransfer'])->name('payments.transfer');
    Route::post('/{application}/konfirmasi', [PaymentController::class, 'konfirmasi'])->name('payments.confirm');
    Route::post('/{application}/addon', [PaymentController::class, 'addAddon'])->name('payments.addon');
    Route::post('/{application}/sengketa', [PaymentController::class, 'sengketa'])->name('payments.sengketa');
    Route::get('/{application}/bukti', [PaymentController::class, 'buktiStream'])->name('payments.bukti');
});

// ==================== MEDIA PROFIL EXTRAS (lintas role: pemilik, Admin, Client) ====================
// RF-14 & CLAUDE.md §5: foto/video boleh dilihat pemilik, Admin, maupun Client -
// otorisasi granular ditegakkan di ProfileController::pastikanBolehLihatMedia(),
// bukan lewat role middleware, karena resource yang sama diakses 3 pihak berbeda.

Route::middleware('auth')->prefix('media')->group(function () {
    Route::get('/foto/{extrasProfile}', [ProfileController::class, 'fotoStream'])->name('extras.media.foto');
    Route::get('/video/{extrasProfile}', [ProfileController::class, 'videoStream'])->name('extras.media.video');
    Route::get('/foto-tambahan/{extrasProfile}/{slot}', [ProfileController::class, 'fotoTambahanStream'])
        ->whereNumber('slot')->name('extras.media.foto-tambahan');
});
