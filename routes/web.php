<?php

use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\CastingProjectController as AdminCastingProjectController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FeeNegotiationController as AdminFeeNegotiationController;
use App\Http\Controllers\Admin\MarginRecapController;
use App\Http\Controllers\Admin\RecapController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\WorkHistoryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Cd\DashboardController as CdDashboardController;
use App\Http\Controllers\Cd\ReviewController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\Extras\CastingProjectController as ExtrasCastingProjectController;
use App\Http\Controllers\Extras\DashboardController as ExtrasDashboardController;
use App\Http\Controllers\Extras\FeeNegotiationController as ExtrasFeeNegotiationController;
use App\Http\Controllers\Extras\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicEventController;
use App\Http\Controllers\SuperAdmin\AdminManagementController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\MonitoringController;
use App\Http\Controllers\SuperAdmin\ProjectAssignmentController;
use Illuminate\Support\Facades\Route;

// RF-55: homepage compro publik. Bukan auth gate: tetap tampil apa pun
// status login user, konten CTA-nya saja yang beda (lihat HomeController).
Route::get('/', [HomeController::class, 'index'])->name('home');

// RF-56: link publik pendaftaran per event, dibagikan Admin lewat WA.
// Sengaja di luar grup middleware auth/guest — guest maupun user login
// (extras/admin/CD) manapun boleh buka, otorisasi granular di controller.
Route::get('/event/{token}', [PublicEventController::class, 'show'])->name('public.event.show');

// Pintu masuk universal setelah login (dipakai mis. link "kembali ke
// dashboard" generik) — lempar ke dashboard sesuai role via
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

    // RF-02: registrasi Casting Director — URL ini TIDAK ditautkan dari
    // halaman publik mana pun, dibagikan manual oleh Admin ke pihak client/PH.
    Route::get('/register/casting-director', [RegisterController::class, 'showCastingDirector'])
        ->name('register.cd');
    Route::post('/register/casting-director', [RegisterController::class, 'registerCastingDirector'])
        ->middleware('throttle:5,1');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
})->name('privacy-policy');

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ==================== EXTRAS ====================

Route::middleware(['auth', 'role:extras'])->prefix('extras')->group(function () {
    Route::get('/dashboard', [ExtrasDashboardController::class, 'index'])->name('extras.dashboard');

    Route::get('/profil', [ProfileController::class, 'show'])->name('extras.profile.show');
    Route::get('/profil/lengkapi', [ProfileController::class, 'edit'])->name('extras.profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('extras.profile.update');
    Route::post('/profil/foto', [ProfileController::class, 'uploadFoto'])->name('extras.profile.foto');
    Route::post('/profil/video', [ProfileController::class, 'uploadVideo'])->name('extras.profile.video');
    Route::post('/profil/foto-tambahan/{slot}', [ProfileController::class, 'uploadFotoTambahan'])
        ->whereNumber('slot')->name('extras.profile.foto-tambahan');
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
});

// ==================== ADMIN (Default + sub-role) ====================

Route::middleware(['auth', 'role:admin_default,admin_talco,admin_korlap,admin_sosmed'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // RF-43/RF-44: Riwayat Kerja & Status Gaji — akses SEMUA tipe Admin,
        // termasuk Talco/Sosmed yang read-only footprint-nya cuma di sini.
        Route::get('/riwayat-kerja', [WorkHistoryController::class, 'index'])->name('admin.work-history');

        // RF-05: khusus Admin Default — dicek lagi di controller karena
        // sub-role (Talco/Korlap/Sosmed) tidak boleh kelola akun CD/Extras.
        Route::middleware('role:admin_default')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->name('admin.users.index');
            Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
                ->name('admin.users.toggle-status');

            Route::get('/projects', [AdminCastingProjectController::class, 'index'])->name('admin.projects.index');
            Route::get('/projects/create', [AdminCastingProjectController::class, 'create'])->name('admin.projects.create');
            Route::post('/projects', [AdminCastingProjectController::class, 'store'])->name('admin.projects.store');
            Route::get('/projects/{castingProject}/edit', [AdminCastingProjectController::class, 'edit'])->name('admin.projects.edit');
            Route::patch('/projects/{castingProject}', [AdminCastingProjectController::class, 'update'])->name('admin.projects.update');
            Route::patch('/projects/{castingProject}/toggle-status', [AdminCastingProjectController::class, 'toggleStatus'])
                ->name('admin.projects.toggle-status');
            Route::get('/projects/{castingProject}/applicants', [AdminCastingProjectController::class, 'showApplicants'])
                ->name('admin.projects.applicants');
            Route::post('/projects/{castingProject}/assign-cd', [AdminCastingProjectController::class, 'assignCd'])
                ->name('admin.projects.assign-cd');

            Route::patch('/applications/{application}/grade', [ApplicantController::class, 'setGrade'])
                ->name('admin.applications.grade');

            Route::patch('/applications/{application}/reject', [ApplicantController::class, 'reject'])
                ->name('admin.applications.reject');

            Route::post('/applications/{application}/apresiasi', [ApplicantController::class, 'toggleApresiasi'])
                ->name('admin.applications.apresiasi');

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
            Route::post('/applications/{application}/ajukan-ke-cd', [AdminFeeNegotiationController::class, 'ajukanKeCd'])
                ->name('admin.negotiations.ajukan-ke-cd');
            Route::post('/applications/{application}/batalkan', [AdminFeeNegotiationController::class, 'batalkan'])
                ->name('admin.negotiations.batalkan');

            Route::get('/recap', [RecapController::class, 'index'])->name('admin.recap.index');
            Route::get('/recap/export', [RecapController::class, 'export'])->name('admin.recap.export');
        });

        // RF-35: Korlap DAN Admin Default boleh nulis catatan lapangan —
        // beda dari grup role:admin_default murni di atas, jadi route
        // sendiri dengan whitelist role eksplisit (Talco/Sosmed tidak boleh).
        Route::middleware('role:admin_default,admin_korlap')->group(function () {
            Route::post('/applications/{application}/catatan', [ApplicantController::class, 'tambahCatatan'])
                ->name('admin.applications.catatan');

            // SPEC.md Bagian F: halaman baru Korlap, TERPISAH dari
            // admin.projects.applicants (gerbangnya admin_default murni,
            // itu akar bug lama RF-35 — route ada, halamannya tidak
            // terjangkau). Route GET halaman + POST absen sama-sama di
            // sini biar Korlap beneran bisa sampai.
            Route::get('/absensi', [AttendanceController::class, 'index'])->name('admin.attendance.index');
            Route::post('/applications/{application}/absen', [AttendanceController::class, 'store'])
                ->name('admin.attendance.store');
        });
    });

// RF-30: rekap margin — RAHASIA bisnis inti, cuma Admin Default & Super
// Admin (BUKAN sub-role admin manapun). Grup role sendiri, jangan nested di
// grup admin umum (line ~106, isinya termasuk sub-role) atau grup
// role:admin_default murni (tidak termasuk super_admin).
Route::middleware(['auth', 'role:admin_default,super_admin'])->prefix('admin')->group(function () {
    Route::get('/rekap-margin', [MarginRecapController::class, 'index'])->name('admin.recap-margin');
});

Route::middleware(['auth', 'role:admin_default,super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/rekap-margin', [MarginRecapController::class, 'index'])->name('super-admin.recap-margin');
});

// ==================== SUPER ADMIN ====================

Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->group(function () {
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('super-admin.dashboard');

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('super-admin.monitoring');

    Route::get('/admins', [AdminManagementController::class, 'index'])->name('super-admin.admins.index');
    Route::get('/admins/create', [AdminManagementController::class, 'create'])->name('super-admin.admins.create');
    Route::post('/admins', [AdminManagementController::class, 'store'])->name('super-admin.admins.store');
    Route::patch('/admins/{user}/honor', [AdminManagementController::class, 'updateHonor'])->name('super-admin.admins.honor');
    Route::patch('/admins/{user}/toggle-status', [AdminManagementController::class, 'toggleStatus'])->name('super-admin.admins.toggle-status');
    Route::delete('/admins/{user}', [AdminManagementController::class, 'destroy'])->name('super-admin.admins.destroy');

    Route::post('/projects/{castingProject}/assign', [ProjectAssignmentController::class, 'assign'])
        ->name('super-admin.assignments.assign');
    Route::post('/assignments/{assignment}/complete', [ProjectAssignmentController::class, 'markComplete'])
        ->name('super-admin.assignments.complete');
    Route::post('/payrolls/{payroll}/addon', [ProjectAssignmentController::class, 'addAddon'])
        ->name('super-admin.payrolls.addon');
});

// ==================== CASTING DIRECTOR ====================

Route::middleware(['auth', 'role:casting_director'])->prefix('cd')->group(function () {
    Route::get('/dashboard', [CdDashboardController::class, 'index'])->name('cd.dashboard');

    Route::get('/reviews', [ReviewController::class, 'index'])->name('cd.reviews.index');
    Route::post('/reviews', [ReviewController::class, 'review'])->name('cd.reviews.review');
});

// ==================== KONTRAK (lintas role: Admin Default & Extras) ====================
// RF-25/26/27: satu resource yang diakses dua pihak berbeda, otorisasi
// granular ditegakkan di dalam ContractController, bukan lewat role middleware.

Route::middleware('auth')->prefix('kontrak')->group(function () {
    Route::get('/{application}', [ContractController::class, 'show'])->name('contracts.show');
    Route::post('/{application}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
});

// ==================== INVOICE (lintas role: Admin Default & CD) ====================

Route::middleware('auth')->prefix('invoice')->group(function () {
    Route::get('/{castingProject}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::post('/{castingProject}/sign', [InvoiceController::class, 'sign'])->name('invoices.sign');
});

// ==================== PEMBAYARAN EXTRAS (lintas role: Admin Default & Extras) ====================

Route::middleware('auth')->prefix('pembayaran')->group(function () {
    Route::get('/{application}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/{application}/transfer', [PaymentController::class, 'tandaiTransfer'])->name('payments.transfer');
    Route::post('/{application}/konfirmasi', [PaymentController::class, 'konfirmasi'])->name('payments.confirm');
    Route::post('/{application}/addon', [PaymentController::class, 'addAddon'])->name('payments.addon');
});

// ==================== MEDIA PROFIL EXTRAS (lintas role: pemilik, Admin, CD) ====================
// RF-14 & CLAUDE.md §5: foto/video boleh dilihat pemilik, Admin, maupun CD —
// otorisasi granular ditegakkan di ProfileController::pastikanBolehLihatMedia(),
// bukan lewat role middleware, karena resource yang sama diakses 3 pihak berbeda.

Route::middleware('auth')->prefix('media')->group(function () {
    Route::get('/foto/{extrasProfile}', [ProfileController::class, 'fotoStream'])->name('extras.media.foto');
    Route::get('/video/{extrasProfile}', [ProfileController::class, 'videoStream'])->name('extras.media.video');
    Route::get('/foto-tambahan/{extrasProfile}/{slot}', [ProfileController::class, 'fotoTambahanStream'])
        ->whereNumber('slot')->name('extras.media.foto-tambahan');
});
