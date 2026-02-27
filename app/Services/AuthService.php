<?php

namespace App\Services;

use App\Interfaces\AuthRepositoryInterface;
use App\Models\Kuliah;
use App\Models\Pekerjaan;
use App\Models\Perusahaan;
use App\Models\Wirausaha;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private AuthRepositoryInterface $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    public function registerUserAndProfile(array $accountData, array $profileData)
    {
        return DB::transaction(function () use ($accountData, $profileData) {
            // Convert year-only values to proper date format for DB
            if (!empty($profileData['tahun_lulus']) && preg_match('/^\d{4}$/', $profileData['tahun_lulus'])) {
                $profileData['tahun_lulus'] = $profileData['tahun_lulus'] . '-01-01';
            }

            // Handle foto upload — store to disk and replace with path
            if (isset($profileData['foto']) && $profileData['foto'] instanceof \Illuminate\Http\UploadedFile) {
                $profileData['foto'] = $profileData['foto']->store('alumni/foto', 'public');
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
                    Kuliah::create([
                        // ID universitas di-passing dari frontend menggunakan field nama_universitas
                        'id_universitas' => $universitasData['nama_universitas'], 
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
    }

    public function login(array $credentials)
    {
        $user = $this->authRepository->findUserByEmail($credentials['email']);

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user->load(['alumni.jurusan', 'admin']),
            'token' => $token,
        ];
    }

    public function logout($user)
    {
        $user->currentAccessToken()->delete();
    }

    public function getAuthenticatedUser($user)
    {
        return $this->authRepository->findUserById($user->id_users);
    }
}