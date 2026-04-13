<?php

namespace App\Services;

use App\Events\DashboardStatsUpdated;
use App\Interfaces\AuthRepositoryInterface;
use App\Mail\ResetPasswordMail;
use App\Models\Kuliah;
use App\Models\Pekerjaan;
use App\Models\Perusahaan;
use App\Models\Wirausaha;
use App\Rules\EmailNotBanned;
use App\Rules\UniqueEmailExceptRejected;
use App\Traits\GeneratesThumbnail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthService
{
    use GeneratesThumbnail;
    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * Verify a Google ID token and return user info.
     */
    public function verifyGoogleToken(string $idToken): array
    {
        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if ($response->failed()) {
            throw ValidationException::withMessages([
                'google_id_token' => ['Token Google tidak valid atau sudah kadaluarsa.'],
            ]);
        }

        $payload = $response->json();

        // Verify audience matches our client ID
        $clientId = config('services.google.client_id');
        if ($clientId && $payload['aud'] !== $clientId) {
            throw ValidationException::withMessages([
                'google_id_token' => ['Token Google tidak valid untuk aplikasi ini.'],
            ]);
        }

        return [
            'google_id' => $payload['sub'],
            'email' => $payload['email'],
            'name' => $payload['name'] ?? ($payload['given_name'] ?? 'Alumni'),
            'picture' => $payload['picture'] ?? null,
            'email_verified' => $payload['email_verified'] ?? false,
        ];
    }

    /**
     * Login with Google ID token.
     * Skips captcha and password. Finds user by google_id.
     */
    public function loginWithGoogle(string $idToken): array
    {
        $googleUser = $this->verifyGoogleToken($idToken);

        // Try to find user by google_id first
        $user = $this->authRepository->findUserByGoogleId($googleUser['google_id']);

        // If not found by google_id, try by email (might have registered manually)
        if (!$user) {
            $user = $this->authRepository->findUserByEmail($googleUser['email']);
        }

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Akun dengan email ini belum terdaftar. Silakan daftar terlebih dahulu.'],
            ]);
        }

        // If user exists but doesn't have google_id, link it
        if (!$user->google_id) {
            $user->update([
                'google_id' => $googleUser['google_id'],
                'auth_provider' => 'google',
            ]);
        }

        // Check alumni status
        if ($user->alumni) {
            if ($user->alumni->status_create === 'banned') {
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah dibanned dan tidak dapat login.'],
                ]);
            }

            if ($user->alumni->status_create === 'rejected') {
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah ditolak. Silakan daftar ulang.'],
                ]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Calculate can_access_all for alumni users
        $canAccessAll = null;
        if ($user->alumni) {
            $canAccessAll = $this->calculateCanAccessAll($user->id_users);
        }

        return [
            'user' => $user->load(['alumni.jurusan', 'admin']),
            'token' => $token,
            'can_access_all' => $canAccessAll,
        ];
    }

    /**
     * Verify Google token for registration (Step 1).
     * Returns google user data for frontend auto-fill.
     */
    public function registerGoogle(string $idToken): array
    {
        $googleUser = $this->verifyGoogleToken($idToken);

        // Check if email is already registered (non-rejected)
        $validator = Validator::make(
            ['email' => $googleUser['email']],
            ['email' => ['required', 'email', new EmailNotBanned(), new UniqueEmailExceptRejected()]]
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $googleUser;
    }

    public function registerUserAndProfile(array $accountData, array $profileData)
    {
        $result = DB::transaction(function () use ($accountData, $profileData) {
            // Hapus akun lama jika status_create = 'rejected'
            $this->authRepository->deleteRejectedUserByEmail($accountData['email']);

            // For Google auth: generate a random password (user won't use it)
            if (!empty($accountData['google_id']) && empty($accountData['password'])) {
                $accountData['password'] = Hash::make($accountData['google_id'] . '_' . uniqid());
            }
            
            // Convert year-only values to proper date format for DB
            if (!empty($profileData['tahun_lulus']) && preg_match('/^\d{4}$/', $profileData['tahun_lulus'])) {
                $profileData['tahun_lulus'] = $profileData['tahun_lulus'] . '-01-01';
            }

            // Handle foto upload — store to disk with thumbnail
            if (isset($profileData['foto']) && $profileData['foto'] instanceof \Illuminate\Http\UploadedFile) {
                try {
                    $result = $this->storeWithThumbnail($profileData['foto'], 'alumni/foto');
                    $profileData['foto'] = $result['path'];
                } catch (\Error $e) {
                    // Fallback: If Intervention Image not installed, just store without thumbnail
                    $profileData['foto'] = $profileData['foto']->store('alumni/foto', 'public');
                }
            }

            // --- 1. EKSTRAK DATA RELASI & KARIER SEBELUM MEMBUAT ALUMNI ---
            $skills = $profileData['skills'] ?? null;
            $socialMedia = $profileData['social_media'] ?? null;
            
            $idStatus = $profileData['id_status'] ?? null;
            $tahunMulai = $profileData['tahun_mulai'] ?? null;
            $tahunSelesai = $profileData['tahun_selesai'] ?? null;
            
            $pekerjaanData = $profileData['pekerjaan'] ?? null;
            $universitasData = $profileData['universitas'] ?? null;
            $wirausahaData = $profileData['wirausaha'] ?? null;

            // Hapus data relasi dari array $profileData agar tidak terjadi error mass-assignment di model Alumni
            unset(
                $profileData['skills'], $profileData['social_media'],
                $profileData['id_status'], $profileData['tahun_mulai'], $profileData['tahun_selesai'],
                $profileData['pekerjaan'], $profileData['universitas'], $profileData['wirausaha']
            );

            // --- 2. CREATE USER & ALUMNI ---
            $user = $this->authRepository->createUser($accountData);
            $alumni = $this->authRepository->createAlumniProfile($user->id_users, $profileData);

            // --- 3. SIMPAN SKILLS & SOCIAL MEDIA ---
            if (!empty($skills)) {
                $alumni->skills()->sync($skills);
            }

            if (!empty($socialMedia)) {
                $syncData = [];
                foreach ($socialMedia as $sm) {
                    if (isset($sm['id_sosmed']) && isset($sm['url'])) {
                        $syncData[$sm['id_sosmed']] = ['url' => $sm['url']];
                    }
                }
                $alumni->socialMedia()->sync($syncData);
            }

            // --- 4. SIMPAN STATUS KARIER (Riwayat Status) ---
            if ($idStatus) {
                $riwayat = $alumni->riwayatStatus()->create([
                    'id_status' => $idStatus,
                    'tahun_mulai' => $tahunMulai,
                    'tahun_selesai' => $tahunSelesai,
                ]);

                // Detail Jika Status Bekerja
                if (!empty($pekerjaanData)) {
                    $perusahaan = Perusahaan::firstOrCreate(
                        ['nama_perusahaan' => $pekerjaanData['nama_perusahaan']],
                        [
                            'id_kota' => $pekerjaanData['id_kota'] ??null,
                            'jalan' => $pekerjaanData['jalan'] ?? '',
                        ]
                    );

                    Pekerjaan::create([
                        'posisi' => $pekerjaanData['posisi'] ?? '-',
                        'id_perusahaan' => $perusahaan->id_perusahaan,
                        'id_riwayat' => $riwayat->id_riwayat,
                    ]);
                }

                // Detail Jika Status Kuliah
                if (!empty($universitasData)) {
                    $namaAtauIdUniversitas = $universitasData['nama_universitas'];
                    $idUniversitas = null;

                    // Cek apakah data yang dikirim adalah ID (numerik) atau nama universitas baru
                    if (is_numeric($namaAtauIdUniversitas)) {
                        $idUniversitas = $namaAtauIdUniversitas;
                    } else {
                        // Jika teks (nama universitas baru), cari atau buat data universitas baru
                        $universitas = \App\Models\Universitas::firstOrCreate([
                            'nama_universitas' => $namaAtauIdUniversitas
                        ]);
                        $idUniversitas = $universitas->id_universitas;
                    }

                    Kuliah::create([
                        'id_universitas' => $idUniversitas, 
                        'id_jurusanKuliah' => $universitasData['id_jurusanKuliah'],
                        'jalur_masuk' => $universitasData['jalur_masuk'],
                        'jenjang' => $universitasData['jenjang'],
                        'id_riwayat' => $riwayat->id_riwayat,
                    ]);
                }

                // Detail Jika Status Wirausaha
                if (!empty($wirausahaData)) {
                    Wirausaha::create([
                        'id_bidang' => $wirausahaData['id_bidang'],
                        'nama_usaha' => $wirausahaData['nama_usaha'],
                        'id_riwayat' => $riwayat->id_riwayat,
                    ]);
                }
            }

            return $user->createToken('auth_token')->plainTextToken;
        });

        // Broadcast to admin dashboard after transaction commits
        broadcast(new DashboardStatsUpdated('new_registration'))->toOthers();

        return $result;
    }

    public function login(array $credentials)
    {
        // ── Verify captcha (cache-based, not session) ───
        $captchaInput = strtolower(trim($credentials['captcha_token'] ?? ''));
        $captchaKey = $credentials['captcha_key'] ?? '';

        // Pull = get + delete (one-time use)
        $storedPhrase = $captchaKey ? \Illuminate\Support\Facades\Cache::pull($captchaKey) : null;

        if (!$captchaInput || !$storedPhrase || $captchaInput !== $storedPhrase) {
            throw ValidationException::withMessages([
                'captcha_token' => ['Captcha tidak valid. Silakan coba lagi.'],
            ]);
        }

        // ── Verify credentials ──────────────────────────
        $user = $this->authRepository->findUserByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Cek status akun alumni
        if ($user->alumni) {
            if ($user->alumni->status_create === 'banned') {
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah dibanned dan tidak dapat login.'],
                ]);
            }
            
            if ($user->alumni->status_create === 'rejected') {
                throw ValidationException::withMessages([
                    'email' => ['Akun Anda telah ditolak. Silakan daftar ulang.'],
                ]);
            }
            
            // Pending alumni diperbolehkan login — mereka bisa melihat status pengajuan
            // tetapi akses fitur dibatasi oleh middleware EnsureAlumniVerified
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Calculate can_access_all for alumni users
        $canAccessAll = null;
        if ($user->alumni) {
            $canAccessAll = $this->calculateCanAccessAll($user->id_users);
        }

        return [
            'user' => $user->load(['alumni.jurusan', 'admin']),
            'token' => $token,
            'can_access_all' => $canAccessAll,
        ];
    }

    /**
     * Check if the user has completed all required kuesioner for their current career status.
     * CACHED untuk menghindari query berulang.
     */

    private function hasCompletedKuesioner(int $userId): bool
    {
        \Illuminate\Support\Facades\Cache::forget("user:{$userId}:kuesioner_completed");

        return \Illuminate\Support\Facades\Cache::remember(
            "user:{$userId}:kuesioner_completed",
            300, // Cache 5 menit
            function () use ($userId) {
                // Get current status id
                $alumni = \App\Models\Alumni::where('id_users', $userId)->first();
                
                if (!$alumni) return false;

        $latestRiwayat = \App\Models\RiwayatStatus::where('id_alumni', $alumni->id_alumni)
            ->latest('id_riwayat')
            ->first();

        $statusId = $latestRiwayat?->id_status;

        // Get active kuesioner for this status
        $query = \App\Models\Kuesioner::withCount('pertanyaan')
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_publikasi')
            ->where(function ($q) {
                $q->whereNull('tanggal_mulai')
                    ->orWhere('tanggal_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now());
            });

        // Include BOTH status-specific AND global kuesioner (id_status IS NULL)
        $query->where(function ($q) use ($statusId) {
            $q->whereNull('id_status');
            if ($statusId !== null) {
                $q->orWhere('id_status', $statusId);
            }
        });

        $activeKuesioner = $query->get();

        if ($activeKuesioner->isEmpty()) {
            return true;
        }

        // Check if all kuesioner are completed
        $kuesionerIds = $activeKuesioner->pluck('id_kuesioner');
        $answeredCounts = \App\Models\Pertanyaan::whereIn('id_kuesioner', $kuesionerIds)
            ->whereHas('jawaban', fn($q) => $q->where('id_user', $userId))
            ->selectRaw('id_kuesioner, COUNT(*) as answered')
            ->groupBy('id_kuesioner')
            ->pluck('answered', 'id_kuesioner');

                foreach ($activeKuesioner as $kuesioner) {
                    if ($kuesioner->pertanyaan_count === 0) continue;
                    $answered = $answeredCounts->get($kuesioner->id_kuesioner, 0);
                    if ($answered < $kuesioner->pertanyaan_count) {
                        return false;
                    }
                }

                return true;
            }
        );
    }

    /**
     * Calculate can_access_all for alumni users.
     * Returns true if alumni is verified and has completed all required kuesioner.
     * CACHED untuk menghindari query berulang.
     */
    public function calculateCanAccessAll(int $userId): bool
    {
        \Illuminate\Support\Facades\Cache::forget("user:{$userId}:can_access_all");

        return \Illuminate\Support\Facades\Cache::remember(
            "user:{$userId}:can_access_all",
            600, // Cache 10 menit
            function () use ($userId) {
                $alumni = \App\Models\Alumni::where('id_users', $userId)->first();
                
                if (!$alumni) return false;

                $isVerified = $alumni->status_create === 'ok';
                $hasCompletedKuesioner = $this->hasCompletedKuesioner($userId);
                return $isVerified && $hasCompletedKuesioner;
            }
        );
    }

    public function logout($user)
    {
        $user->currentAccessToken()->delete();
    }

    public function getAuthenticatedUser($user)
    {
        return $this->authRepository->findUserById($user->id_users);
    }

    /**
     * Send a password reset OTP to the user's email.
     */
    public function forgotPassword(string $email): void
    {
        $user = $this->authRepository->findUserByEmail($email);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak ditemukan dalam sistem.'],
            ]);
        }

        // Generate 6-digit OTP token
        $token = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->authRepository->createPasswordResetToken($user->email_users, $token);

        // Send email with OTP
        $frontendUrl = config('app.frontend_url', 'http://localhost:5173');
        Mail::to($user->email_users)->send(new ResetPasswordMail($token, $frontendUrl));
    }

    /**
     * Verify the OTP token and reset the user's password.
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $user = $this->authRepository->findUserByEmail($email);

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email tidak ditemukan dalam sistem.'],
            ]);
        }

        $resetRecord = $this->authRepository->findPasswordResetToken($user->email_users, $token);

        if (!$resetRecord) {
            throw ValidationException::withMessages([
                'token' => ['Kode OTP tidak valid atau sudah kadaluarsa.'],
            ]);
        }

        // Update password
        $this->authRepository->updatePassword($user->id_users, $newPassword);

        // Delete used token
        $this->authRepository->deletePasswordResetToken($user->email_users);

        // Revoke all existing tokens (force re-login)
        $user->tokens()->delete();
    }
}