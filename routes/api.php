<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
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
use App\Http\Controllers\Api\PengaturanTampilanController;
use App\Http\Controllers\Api\KemitraanController;
use App\Http\Controllers\Api\MetaDataController;
use App\Http\Controllers\Api\SebaranAlumniController;
use App\Http\Controllers\Api\GeocodeController;
use App\Http\Controllers\Api\Alumni\ConnectionController;
use App\Http\Controllers\Api\Alumni\MessageController;

// PUBLIC ROUTES

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/validate-email', [AuthController::class, 'validateEmail']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Google Auth routes
Route::post('/auth/google/login', [AuthController::class, 'googleLogin']);
Route::post('/auth/google/register', [AuthController::class, 'googleRegister']);

// CAPTCHA routes
Route::get('/captcha/generate', [CaptchaController::class, 'generate']);
Route::post('/captcha/verify', [CaptchaController::class, 'verify']);
Route::get('/captcha/refresh', [CaptchaController::class, 'refresh']);

// Geocode API (for map picker - public so registration can use it)
Route::prefix('geocode')->group(function () {
    Route::get('/reverse', [GeocodeController::class, 'reverse']);
    Route::get('/search', [GeocodeController::class, 'search']);
});

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

// Public display settings (for ThemeContext on page load)
Route::get('/settings/tampilan', [PengaturanTampilanController::class, 'show']);

// Public metadata
Route::get('/metadata', [MetaDataController::class, 'index']);

// PROTECTED ROUTES (AUTH)

Route::middleware(['auth:sanctum', 'token.expiration'])->group(function () {

    // Broadcasting auth (for Reverb WebSocket private channels)
    Broadcast::routes();

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

        // Social media pending management
        Route::put('/profile/pending-social/{pendingId}', [ProfileController::class, 'updatePendingSocialMedia']);
        Route::delete('/profile/pending-social/{pendingId}', [ProfileController::class, 'cancelPendingSocialMedia']);

        // General pending profile update management (personal_info, etc.)
        Route::delete('/profile/pending/{pendingId}', [ProfileController::class, 'cancelPendingProfileUpdate']);
        
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
        // Pending deskripsi karier management
        Route::put('/deskripsi-karier/pending/{pendingId}', [DeskripsiKarierController::class, 'updatePending']);
        Route::delete('/deskripsi-karier/pending/{pendingId}', [DeskripsiKarierController::class, 'cancelPending']);


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

            // Alumni Connections (LinkedIn-style mutual connection + block)
            Route::prefix('connections')->group(function () {
                // List endpoints (harus di atas {id} routes agar tidak konflik)
                Route::get('/', [ConnectionController::class, 'myConnections']);
                Route::get('/pending', [ConnectionController::class, 'pendingRequests']);
                Route::get('/sent', [ConnectionController::class, 'sentRequests']);
                Route::get('/stats', [ConnectionController::class, 'myStats']);
                Route::get('/suggestions', [ConnectionController::class, 'suggestions']);
                Route::get('/blocked', [ConnectionController::class, 'blockedList']);
                Route::get('/mutual/{id}', [ConnectionController::class, 'mutualConnections']);

                // Action endpoints (per target alumni ID)
                Route::post('/{id}/request', [ConnectionController::class, 'sendRequest']);
                Route::post('/{id}/accept', [ConnectionController::class, 'acceptRequest']);
                Route::post('/{id}/reject', [ConnectionController::class, 'rejectRequest']);
                Route::delete('/{id}', [ConnectionController::class, 'removeConnection']);
                Route::post('/{id}/block', [ConnectionController::class, 'blockAlumni']);
                Route::delete('/{id}/block', [ConnectionController::class, 'unblockAlumni']);

                // View endpoints (per target alumni ID)
                Route::get('/{id}/connections', [ConnectionController::class, 'alumniConnections']);
                Route::get('/{id}/stats', [ConnectionController::class, 'alumniStats']);
                Route::get('/{id}/status', [ConnectionController::class, 'connectionStatus']);
            });

            // =====================
            // MESSAGING (Real-time Chat via Reverb)
            // =====================
            Route::prefix('messages')->group(function () {
                // Conversation list & stats
                Route::get('/conversations', [MessageController::class, 'conversations']);
                Route::get('/unread-count', [MessageController::class, 'unreadCount']);
                Route::get('/contacts', [MessageController::class, 'contacts']);

                // Create conversations
                Route::post('/conversations/private', [MessageController::class, 'getOrCreatePrivate']);
                Route::post('/conversations/group', [MessageController::class, 'createGroup']);

                // Single conversation
                Route::get('/conversations/{id}', [MessageController::class, 'showConversation']);
                Route::delete('/conversations/{id}', [MessageController::class, 'deleteConversation']);
                Route::match(['put', 'post'], '/conversations/{id}/group', [MessageController::class, 'updateGroup']);
                Route::post('/conversations/{id}/leave', [MessageController::class, 'leaveConversation']);

                // Conversation settings
                Route::post('/conversations/{id}/pin', [MessageController::class, 'togglePin']);
                Route::post('/conversations/{id}/mute', [MessageController::class, 'toggleMute']);

                // Messages within a conversation
                Route::get('/conversations/{id}/messages', [MessageController::class, 'messages']);
                Route::post('/conversations/{id}/messages', [MessageController::class, 'sendMessage']);

                // Read receipts & typing
                Route::post('/conversations/{id}/read', [MessageController::class, 'markAsRead']);
                Route::post('/conversations/{id}/typing', [MessageController::class, 'typing']);

                // Delete individual message
                Route::delete('/{id}', [MessageController::class, 'deleteMessage']);
            });
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
        Route::get('/alumni-featured', [AdminController::class, 'getFeaturedAlumni']);
        Route::put('/alumni-featured', [AdminController::class, 'syncFeaturedAlumni']);
        Route::post('/alumni/{id}/featured', [AdminController::class, 'setFeaturedAlumni']);
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

            // Data Wirausaha
            Route::get('/wirausaha', [StatusKarierController::class, 'wirausaha']);
            Route::post('/wirausaha', [StatusKarierController::class, 'storeWirausaha']);
            Route::put('/wirausaha/{id}', [StatusKarierController::class, 'updateWirausaha']);
            Route::delete('/wirausaha/{id}', [StatusKarierController::class, 'destroyWirausaha']);

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

        // Pengaturan Tampilan
        Route::post('/pengaturan-tampilan/revert', [PengaturanTampilanController::class, 'revert']);
        Route::post('/pengaturan-tampilan/reset', [PengaturanTampilanController::class, 'resetToDefault']);
        Route::get('/pengaturan-tampilan', [PengaturanTampilanController::class, 'show']);
        Route::post('/pengaturan-tampilan', [PengaturanTampilanController::class, 'update']);
        
        // Kemitraan (Partnership) Management
        Route::prefix('kemitraan')->group(function () {
            // Mitra Universitas
            Route::get('/universitas', [KemitraanController::class, 'indexUniversitas']);
            Route::post('/universitas', [KemitraanController::class, 'storeUniversitas']);
            Route::match(['put', 'post'], '/universitas/{id}', [KemitraanController::class, 'updateUniversitas']);
            Route::delete('/universitas/{id}', [KemitraanController::class, 'destroyUniversitas']);

            // Mitra Perusahaan
            Route::get('/perusahaan', [KemitraanController::class, 'indexPerusahaan']);
            Route::post('/perusahaan', [KemitraanController::class, 'storePerusahaan']);
            Route::match(['put', 'post'], '/perusahaan/{id}', [KemitraanController::class, 'updatePerusahaan']);
            Route::delete('/perusahaan/{id}', [KemitraanController::class, 'destroyPerusahaan']);

            // Export
            Route::get('/export', [KemitraanController::class, 'export']);
        });

        // Sebaran Alumni (Mapping)
        Route::prefix('sebaran')->group(function () {
            Route::get('/markers', [SebaranAlumniController::class, 'markers']);
            Route::get('/location/{type}/{id}', [SebaranAlumniController::class, 'alumniAtLocation']);
            Route::get('/filters', [SebaranAlumniController::class, 'filters']);
            Route::get('/stats', [SebaranAlumniController::class, 'stats']);
            Route::get('/heatmap', [SebaranAlumniController::class, 'heatmap']);
            Route::get('/search', [SebaranAlumniController::class, 'search']);
        });

        // Meta Data Management
        Route::post('/metadata', [MetaDataController::class, 'update']);
    });
});