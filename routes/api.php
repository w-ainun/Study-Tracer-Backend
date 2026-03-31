<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlumniController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\LowonganController;
use App\Http\Controllers\Api\KuesionerController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\StatusKarierController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\Alumni\BerandaController;
use App\Http\Controllers\Api\Alumni\LowonganController as AlumniLowonganController;
use App\Http\Controllers\Api\Alumni\AlumniDirectoryController;
use App\Http\Controllers\Api\Alumni\ProfileController;
use App\Http\Controllers\Api\Alumni\DeskripsiKarierController;
use App\Http\Controllers\Api\Alumni\PortofolioController;
use App\Http\Controllers\Api\LandingController;
use App\Http\Controllers\Api\PengumumanController;

// PUBLIC ROUTES

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-email', [AuthController::class, 'validateEmail']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// CAPTCHA routes
Route::get('/captcha/generate', [CaptchaController::class, 'generate']);
Route::post('/captcha/verify', [CaptchaController::class, 'verify']);
Route::get('/captcha/refresh', [CaptchaController::class, 'refresh']);

// Public master data (for registration form dropdowns)
Route::prefix('master')->group(function () {
    Route::get('/provinsi', [MasterDataController::class, 'provinsi']);
    Route::get('/kota', [MasterDataController::class, 'kota']);
    Route::get('/jurusan', [MasterDataController::class, 'jurusan']);
    Route::get('/jurusan-kuliah', [MasterDataController::class, 'jurusanKuliah']);
    Route::get('/skills', [MasterDataController::class, 'skills']);
    Route::post('/skills', [MasterDataController::class, 'storeSkill']); // Allow creating new skills from profile page
    Route::get('/social-media', [MasterDataController::class, 'socialMedia']);
    Route::get('/status', [MasterDataController::class, 'status']);
    Route::get('/bidang-usaha', [MasterDataController::class, 'bidangUsaha']);
    Route::get('/universitas', [MasterDataController::class, 'universitas']);
    Route::get('/tipe-pekerjaan', [MasterDataController::class, 'tipePekerjaan']);
    Route::get('/perusahaan', [MasterDataController::class, 'perusahaan']); 
});

// Public approved lowongan
Route::get('/lowongan/published', [LowonganController::class, 'published']);
Route::get('/lowongan/{id}', [LowonganController::class, 'show']);

// Public published kuesioner
Route::get('/kuesioner/published', [KuesionerController::class, 'published']);

// Landing page stats
Route::get('/landing/stats', [LandingController::class, 'stats']);
Route::get('/landing/featured-jobs', [LandingController::class, 'featuredJobs']);
Route::get('/landing/featured-alumni', [LandingController::class, 'featuredAlumni']);

// PROTECTED ROUTES (AUTH)

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Alumni Routes 
    Route::middleware('role:alumni')->prefix('alumni')->group(function () {
        // Always accessible (even if not verified)
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::match(['put', 'post'], '/profile', [ProfileController::class, 'update']);
        Route::post('/career-status', [ProfileController::class, 'updateCareerStatus']);
        Route::put('/career-status/{id}', [ProfileController::class, 'updateExistingCareerStatus']);
        
        // Skills management (with pending approval)
        Route::put('/profile/skills', [ProfileController::class, 'updateSkills']);
        Route::put('/profile/pending-skills/{pendingId}', [ProfileController::class, 'updatePendingSkills']);
        Route::delete('/profile/pending-skills/{pendingId}', [ProfileController::class, 'cancelPendingSkills']);
        
        Route::get('/beranda', [BerandaController::class, 'index']);
        Route::get('/status-pengajuan', [BerandaController::class, 'statusPengajuan']);

        // Kuesioner (accessible even if not verified)
        Route::get('/kuesioner', [KuesionerController::class, 'indexForAlumni']);
        Route::get('/kuesioner/{id}', [KuesionerController::class, 'showWithPertanyaan']);
        Route::get('/kuesioner/status/{statusId}', [KuesionerController::class, 'publishedByStatus']);
        Route::post('/kuesioner/{kuesionerId}/jawaban', [KuesionerController::class, 'submitAnswers']);

        // Notifikasi (accessible even if not verified)
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

        Route::get('/pengumuman', [PengumumanController::class, 'published']);
Route::get('/pengumuman/{id}', [PengumumanController::class, 'show']);

        // Restricted routes (verified alumni only)
        Route::middleware('alumni.verified')->group(function () {
            // Lowongan for alumni (sorted by skill match)
            Route::get('/lowongan', [AlumniLowonganController::class, 'index']);
            Route::post('/lowongan', [AlumniLowonganController::class, 'store']);        // Submit lowongan (pending approval)
            Route::get('/my-lowongan', [AlumniLowonganController::class, 'myLowongan']); // Own submissions
            Route::get('/lowongan/{id}', [AlumniLowonganController::class, 'show']);

            // Saved lowongan
            Route::get('/saved-lowongan', [AlumniLowonganController::class, 'saved']);
            Route::post('/lowongan/{id}/toggle-save', [AlumniLowonganController::class, 'toggleSave']);


        // Deskripsi Karier (accessible even if not verified)
        Route::get('/deskripsi-karier', [DeskripsiKarierController::class, 'index']); // Own or with ?id_alumni=x
        Route::get('/{id_alumni}/deskripsi-karier', [DeskripsiKarierController::class, 'getByAlumni']); // Specific alumni
        Route::post('/deskripsi-karier', [DeskripsiKarierController::class, 'store']);
        Route::put('/deskripsi-karier/{id}', [DeskripsiKarierController::class, 'update']);
        Route::delete('/deskripsi-karier/{id}', [DeskripsiKarierController::class, 'destroy']);


        // Portofolio (accessible even if not verified)
        Route::post('/portofolio', [PortofolioController::class, 'store']);
        Route::match(['put', 'post'], '/portofolio/{id}', [PortofolioController::class, 'update']);
        Route::delete('/portofolio/{id}', [PortofolioController::class, 'destroy']);
        
        // Pending portofolio operations
        Route::match(['put', 'post'], '/portofolio/pending/{pendingId}', [PortofolioController::class, 'updatePending']);
        Route::delete('/portofolio/pending/{pendingId}', [PortofolioController::class, 'cancelPending']);
            // Alumni directory (Direktori Alumni)
            Route::get('/directory', [AlumniDirectoryController::class, 'index']);
            Route::get('/directory/filters', [AlumniDirectoryController::class, 'filterOptions']);
            Route::get('/directory/{id}', [AlumniDirectoryController::class, 'show']);
        });
    });

    // Admin Routes 
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard-stats', [AdminController::class, 'getStats']);
        Route::get('/user-stats', [AdminController::class, 'getUserManagementStats']);
        Route::get('/lowongan-stats', [AdminController::class, 'getLowonganStats']);
        Route::get('/top-companies', [AdminController::class, 'getTopCompanies']);
        Route::get('/geographic-distribution', [AdminController::class, 'getGeographicDistribution']);

        // User Management
        Route::get('/pending-users', [AdminController::class, 'getPendingUsers']);
        Route::post('/approve-user/{id}', [AdminController::class, 'approveUser']);
        Route::post('/reject-user/{id}', [AdminController::class, 'rejectUser']);
        Route::post('/ban-user/{id}', [AdminController::class, 'banUser']);
        Route::get('/alumni/export', [AdminController::class, 'exportAlumniCsv']); // before {id}
        Route::get('/alumni', [AdminController::class, 'getAllAlumni']);
        Route::get('/alumni/{id}', [AdminController::class, 'getAlumniDetail']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Pending Career Status Updates (for ProfileUpdateRequests)
        Route::get('/pending-career-updates', [AdminController::class, 'getPendingCareerUpdates']);
        Route::post('/career-updates/{id}/approve', [AdminController::class, 'approveCareerUpdate']);
        Route::post('/career-updates/{id}/reject', [AdminController::class, 'rejectCareerUpdate']);

        // Pending Profile Updates (personal_info, skills, social_media, deskripsi_karier, portofolio)
        Route::get('/pending-profile-updates', [AdminController::class, 'getPendingProfileUpdates']);
        Route::post('/profile-updates/{id}/approve', [AdminController::class, 'approveProfileUpdate']);
        Route::post('/profile-updates/{id}/reject', [AdminController::class, 'rejectProfileUpdate']);

        // Lowongan Management
        Route::get('/lowongan', [LowonganController::class, 'index']);
        Route::get('/lowongan/pending', [LowonganController::class, 'pending']);
        Route::post('/lowongan', [LowonganController::class, 'store']);
        Route::match(['put', 'post'], '/lowongan/{id}', [LowonganController::class, 'update']);
        Route::delete('/lowongan/{id}', [LowonganController::class, 'destroy']);
        Route::post('/lowongan/{id}/approve', [LowonganController::class, 'approve']);
        Route::post('/lowongan/{id}/reject', [LowonganController::class, 'reject']);
        Route::post('/lowongan/{id}/repost', [LowonganController::class, 'repost']);
        Route::patch('/lowongan/{id}/status', [LowonganController::class, 'updateStatus']);
        Route::post('/lowongan/auto-close-expired', [LowonganController::class, 'autoCloseExpired']);

        // Kuesioner Management
        Route::get('/kuesioner', [KuesionerController::class, 'index']);
        Route::post('/kuesioner', [KuesionerController::class, 'store']);
        Route::get('/kuesioner/{id}', [KuesionerController::class, 'show']);
        Route::put('/kuesioner/{id}', [KuesionerController::class, 'update']);
        Route::delete('/kuesioner/{id}', [KuesionerController::class, 'destroy']);
        Route::patch('/kuesioner/{id}/status', [KuesionerController::class, 'updateStatus']);
        
        // Kuesioner Statistics
        Route::get('/kuesioner/{id}/statistics', [KuesionerController::class, 'statistics']);

        // Pertanyaan Management
        Route::get('/pertanyaan', [KuesionerController::class, 'getAllPertanyaan']);
        Route::post('/kuesioner/{kuesionerId}/pertanyaan', [KuesionerController::class, 'addPertanyaan']);
        Route::put('/kuesioner/{kuesionerId}/pertanyaan/{pertanyaanId}', [KuesionerController::class, 'updatePertanyaan']);
        Route::delete('/kuesioner/{kuesionerId}/pertanyaan/{pertanyaanId}', [KuesionerController::class, 'deletePertanyaan']);

        // Jawaban Kuesioner (admin view)
        Route::get('/kuesioner/{kuesionerId}/jawaban', [KuesionerController::class, 'listJawaban']);
        Route::get('/kuesioner/{kuesionerId}/jawaban/{alumniId}', [KuesionerController::class, 'jawabanDetail']);

        // Status Karier Management
        Route::prefix('status-karier')->group(function () {
            // Universitas
            Route::get('/universitas', [StatusKarierController::class, 'universitas']);
            Route::post('/universitas', [StatusKarierController::class, 'storeUniversitas']);
            Route::put('/universitas/{id}', [StatusKarierController::class, 'updateUniversitas']);
            Route::delete('/universitas/{id}', [StatusKarierController::class, 'destroyUniversitas']);

            // Program Studi
            Route::get('/prodi', [StatusKarierController::class, 'prodi']);
            Route::post('/prodi', [StatusKarierController::class, 'storeProdi']);
            Route::put('/prodi/{id}', [StatusKarierController::class, 'updateProdi']);
            Route::delete('/prodi/{id}', [StatusKarierController::class, 'destroyProdi']);

            // Bidang Wirausaha
            Route::get('/bidang-usaha', [StatusKarierController::class, 'bidangUsaha']);
            Route::post('/bidang-usaha', [StatusKarierController::class, 'storeBidangUsaha']);
            Route::put('/bidang-usaha/{id}', [StatusKarierController::class, 'updateBidangUsaha']);
            Route::delete('/bidang-usaha/{id}', [StatusKarierController::class, 'destroyBidangUsaha']);

            // Report & Export
            Route::get('/report', [StatusKarierController::class, 'statusDistribution']);
            Route::get('/export', [StatusKarierController::class, 'exportReport']);
        });

        // Master Data CRUD (admin only)
        Route::prefix('master')->group(function () {
            // Provinsi
            Route::get('/provinsi', [MasterDataController::class, 'provinsi']);
            Route::post('/provinsi', [MasterDataController::class, 'storeProvinsi']);
            Route::put('/provinsi/{id}', [MasterDataController::class, 'updateProvinsi']);
            Route::delete('/provinsi/{id}', [MasterDataController::class, 'destroyProvinsi']);

            // Kota
            Route::get('/kota', [MasterDataController::class, 'kota']);
            Route::post('/kota', [MasterDataController::class, 'storeKota']);
            Route::put('/kota/{id}', [MasterDataController::class, 'updateKota']);
            Route::delete('/kota/{id}', [MasterDataController::class, 'destroyKota']);

            // Jurusan
            Route::get('/jurusan', [MasterDataController::class, 'jurusan']);
            Route::post('/jurusan', [MasterDataController::class, 'storeJurusan']);
            Route::put('/jurusan/{id}', [MasterDataController::class, 'updateJurusan']);
            Route::delete('/jurusan/{id}', [MasterDataController::class, 'destroyJurusan']);

            // Jurusan Kuliah
            Route::get('/jurusan-kuliah', [MasterDataController::class, 'jurusanKuliah']);
            Route::post('/jurusan-kuliah', [MasterDataController::class, 'storeJurusanKuliah']);
            Route::put('/jurusan-kuliah/{id}', [MasterDataController::class, 'updateJurusanKuliah']);
            Route::delete('/jurusan-kuliah/{id}', [MasterDataController::class, 'destroyJurusanKuliah']);

            // Skills
            Route::get('/skills', [MasterDataController::class, 'skills']);
            Route::post('/skills', [MasterDataController::class, 'storeSkill']);
            Route::put('/skills/{id}', [MasterDataController::class, 'updateSkill']);
            Route::delete('/skills/{id}', [MasterDataController::class, 'destroySkill']);

            // Social Media
            Route::get('/social-media', [MasterDataController::class, 'socialMedia']);
            Route::post('/social-media', [MasterDataController::class, 'storeSocialMedia']);
            Route::put('/social-media/{id}', [MasterDataController::class, 'updateSocialMedia']);
            Route::delete('/social-media/{id}', [MasterDataController::class, 'destroySocialMedia']);

            // Status
            Route::get('/status', [MasterDataController::class, 'status']);
            Route::post('/status', [MasterDataController::class, 'storeStatus']);
            Route::put('/status/{id}', [MasterDataController::class, 'updateStatus']);
            Route::delete('/status/{id}', [MasterDataController::class, 'destroyStatus']);

            // Bidang Usaha
            Route::get('/bidang-usaha', [MasterDataController::class, 'bidangUsaha']);
            Route::post('/bidang-usaha', [MasterDataController::class, 'storeBidangUsaha']);
            Route::put('/bidang-usaha/{id}', [MasterDataController::class, 'updateBidangUsaha']);
            Route::delete('/bidang-usaha/{id}', [MasterDataController::class, 'destroyBidangUsaha']);

            // Perusahaan
            Route::get('/perusahaan', [MasterDataController::class, 'perusahaan']);
            Route::post('/perusahaan', [MasterDataController::class, 'storePerusahaan']);
            Route::put('/perusahaan/{id}', [MasterDataController::class, 'updatePerusahaan']);
            Route::delete('/perusahaan/{id}', [MasterDataController::class, 'destroyPerusahaan']);

            // Universitas
            Route::get('/universitas', [MasterDataController::class, 'universitas']);
            Route::post('/universitas', [MasterDataController::class, 'storeUniversitas']);

            // Tipe Pekerjaan
            Route::get('/tipe-pekerjaan', [MasterDataController::class, 'tipePekerjaan']);
        });

        // Pengumuman Management
        Route::get('/pengumuman/stats', [PengumumanController::class, 'stats']);
        Route::get('/pengumuman', [PengumumanController::class, 'index']);
        Route::post('/pengumuman', [PengumumanController::class, 'store']);
        Route::get('/pengumuman/{id}', [PengumumanController::class, 'show']);
        Route::match(['put', 'post'], '/pengumuman/{id}', [PengumumanController::class, 'update']);
        Route::delete('/pengumuman/{id}', [PengumumanController::class, 'destroy']);
        Route::patch('/pengumuman/{id}/pin', [PengumumanController::class, 'togglePin']);
    });
});