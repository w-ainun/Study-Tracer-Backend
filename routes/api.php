<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlumniController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\LowonganController;
use App\Http\Controllers\Api\KuesionerController;
use App\Http\Controllers\Api\MasterDataController;
use App\Http\Controllers\Api\StatusKarierController;

// ╔══════════════════════════════════════════════════════╗
// ║                  PUBLIC ROUTES                       ║
// ╚══════════════════════════════════════════════════════╝

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public master data (for registration form dropdowns)
Route::prefix('master')->group(function () {
    Route::get('/provinsi', [MasterDataController::class, 'provinsi']);
    Route::get('/kota', [MasterDataController::class, 'kota']);
    Route::get('/jurusan', [MasterDataController::class, 'jurusan']);
    Route::get('/jurusan-kuliah', [MasterDataController::class, 'jurusanKuliah']);
    Route::get('/skills', [MasterDataController::class, 'skills']);
    Route::get('/social-media', [MasterDataController::class, 'socialMedia']);
    Route::get('/status', [MasterDataController::class, 'status']);
    Route::get('/bidang-usaha', [MasterDataController::class, 'bidangUsaha']);
    Route::get('/universitas', [MasterDataController::class, 'universitas']);
    Route::get('/tipe-pekerjaan', [MasterDataController::class, 'tipePekerjaan']);
});

// Public approved lowongan
Route::get('/lowongan/published', [LowonganController::class, 'published']);
Route::get('/lowongan/{id}', [LowonganController::class, 'show']);

// Public published kuesioner
Route::get('/kuesioner/published', [KuesionerController::class, 'published']);

// ╔══════════════════════════════════════════════════════╗
// ║               PROTECTED ROUTES (AUTH)                ║
// ╚══════════════════════════════════════════════════════╝

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ── Alumni Routes ────────────────────────────────────
    Route::middleware('role:alumni')->prefix('alumni')->group(function () {
        Route::get('/profile', [AlumniController::class, 'profile']);
        Route::put('/profile', [AlumniController::class, 'updateProfile']);
        Route::post('/career-status', [AlumniController::class, 'updateCareerStatus']);

        // Saved lowongan
        Route::get('/saved-lowongan', [LowonganController::class, 'savedByUser']);
        Route::post('/lowongan/{id}/toggle-save', [LowonganController::class, 'toggleSave']);

        // Kuesioner jawaban
        Route::get('/kuesioner/{id}', [KuesionerController::class, 'showWithPertanyaan']);
        Route::get('/kuesioner/status/{statusId}', [KuesionerController::class, 'publishedByStatus']);
        Route::post('/kuesioner/{kuesionerId}/jawaban', [KuesionerController::class, 'submitAnswers']);
    });

    // ── Admin Routes ─────────────────────────────────────
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

        // Lowongan Management
        Route::get('/lowongan', [LowonganController::class, 'index']);
        Route::get('/lowongan/pending', [LowonganController::class, 'pending']);
        Route::post('/lowongan', [LowonganController::class, 'store']);
        Route::put('/lowongan/{id}', [LowonganController::class, 'update']);
        Route::delete('/lowongan/{id}', [LowonganController::class, 'destroy']);
        Route::post('/lowongan/{id}/approve', [LowonganController::class, 'approve']);
        Route::post('/lowongan/{id}/reject', [LowonganController::class, 'reject']);
        Route::post('/lowongan/{id}/repost', [LowonganController::class, 'repost']);

        // Kuesioner Management
        Route::get('/kuesioner', [KuesionerController::class, 'index']);
        Route::post('/kuesioner', [KuesionerController::class, 'store']);
        Route::get('/kuesioner/{id}', [KuesionerController::class, 'show']);
        Route::put('/kuesioner/{id}', [KuesionerController::class, 'update']);
        Route::delete('/kuesioner/{id}', [KuesionerController::class, 'destroy']);
        Route::patch('/kuesioner/{id}/status', [KuesionerController::class, 'updateStatus']);

        // Pertanyaan Management
        Route::get('/pertanyaan', [KuesionerController::class, 'getAllPertanyaan']);
        Route::post('/pertanyaan', [KuesionerController::class, 'storePertanyaan']); // New: Direct pertanyaan without kuesioner ID
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
    });
});