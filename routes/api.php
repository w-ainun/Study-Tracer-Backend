<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AlumniController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\LowonganController;
use App\Http\Controllers\Api\KuesionerController;
use App\Http\Controllers\Api\MasterDataController;

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
        Route::post('/kuesioner/{kuesionerId}/jawaban', [KuesionerController::class, 'submitAnswers']);
    });

    // ── Admin Routes ─────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('/dashboard-stats', [AdminController::class, 'getStats']);

        // User Management
        Route::get('/pending-users', [AdminController::class, 'getPendingUsers']);
        Route::post('/approve-user/{id}', [AdminController::class, 'approveUser']);
        Route::post('/reject-user/{id}', [AdminController::class, 'rejectUser']);
        Route::get('/alumni', [AdminController::class, 'getAllAlumni']);
        Route::get('/alumni/{id}', [AdminController::class, 'getAlumniDetail']);
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);

        // Lowongan Management
        Route::get('/lowongan', [LowonganController::class, 'index']);
        Route::post('/lowongan', [LowonganController::class, 'store']);
        Route::put('/lowongan/{id}', [LowonganController::class, 'update']);
        Route::delete('/lowongan/{id}', [LowonganController::class, 'destroy']);
        Route::get('/lowongan/pending', [LowonganController::class, 'pending']);
        Route::post('/lowongan/{id}/approve', [LowonganController::class, 'approve']);
        Route::post('/lowongan/{id}/reject', [LowonganController::class, 'reject']);

        // Kuesioner Management
        Route::get('/kuesioner', [KuesionerController::class, 'index']);
        Route::post('/kuesioner', [KuesionerController::class, 'store']);
        Route::get('/kuesioner/{id}', [KuesionerController::class, 'show']);
        Route::put('/kuesioner/{id}', [KuesionerController::class, 'update']);
        Route::delete('/kuesioner/{id}', [KuesionerController::class, 'destroy']);

        // Pertanyaan within Kuesioner
        Route::post('/kuesioner/{kuesionerId}/pertanyaan', [KuesionerController::class, 'addPertanyaan']);
        Route::put('/kuesioner/{kuesionerId}/pertanyaan/{pertanyaanId}', [KuesionerController::class, 'updatePertanyaan']);
        Route::delete('/kuesioner/{kuesionerId}/pertanyaan/{pertanyaanId}', [KuesionerController::class, 'deletePertanyaan']);

        // Master Data CRUD (admin only)
        Route::prefix('master')->group(function () {
            // Provinsi
            Route::post('/provinsi', [MasterDataController::class, 'storeProvinsi']);
            Route::put('/provinsi/{id}', [MasterDataController::class, 'updateProvinsi']);
            Route::delete('/provinsi/{id}', [MasterDataController::class, 'destroyProvinsi']);

            // Kota
            Route::post('/kota', [MasterDataController::class, 'storeKota']);
            Route::put('/kota/{id}', [MasterDataController::class, 'updateKota']);
            Route::delete('/kota/{id}', [MasterDataController::class, 'destroyKota']);

            // Jurusan
            Route::post('/jurusan', [MasterDataController::class, 'storeJurusan']);
            Route::put('/jurusan/{id}', [MasterDataController::class, 'updateJurusan']);
            Route::delete('/jurusan/{id}', [MasterDataController::class, 'destroyJurusan']);

            // Jurusan Kuliah
            Route::post('/jurusan-kuliah', [MasterDataController::class, 'storeJurusanKuliah']);
            Route::put('/jurusan-kuliah/{id}', [MasterDataController::class, 'updateJurusanKuliah']);
            Route::delete('/jurusan-kuliah/{id}', [MasterDataController::class, 'destroyJurusanKuliah']);

            // Skills
            Route::post('/skills', [MasterDataController::class, 'storeSkill']);
            Route::put('/skills/{id}', [MasterDataController::class, 'updateSkill']);
            Route::delete('/skills/{id}', [MasterDataController::class, 'destroySkill']);

            // Social Media
            Route::post('/social-media', [MasterDataController::class, 'storeSocialMedia']);
            Route::put('/social-media/{id}', [MasterDataController::class, 'updateSocialMedia']);
            Route::delete('/social-media/{id}', [MasterDataController::class, 'destroySocialMedia']);

            // Status
            Route::post('/status', [MasterDataController::class, 'storeStatus']);
            Route::put('/status/{id}', [MasterDataController::class, 'updateStatus']);
            Route::delete('/status/{id}', [MasterDataController::class, 'destroyStatus']);

            // Bidang Usaha
            Route::post('/bidang-usaha', [MasterDataController::class, 'storeBidangUsaha']);
            Route::put('/bidang-usaha/{id}', [MasterDataController::class, 'updateBidangUsaha']);
            Route::delete('/bidang-usaha/{id}', [MasterDataController::class, 'destroyBidangUsaha']);

            // Perusahaan
            Route::get('/perusahaan', [MasterDataController::class, 'perusahaan']);
            Route::post('/perusahaan', [MasterDataController::class, 'storePerusahaan']);
            Route::put('/perusahaan/{id}', [MasterDataController::class, 'updatePerusahaan']);
            Route::delete('/perusahaan/{id}', [MasterDataController::class, 'destroyPerusahaan']);

            // Universitas
            Route::post('/universitas', [MasterDataController::class, 'storeUniversitas']);
        });
    });
});