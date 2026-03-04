<?php

namespace App\Http\Middleware;

use App\Interfaces\Alumni\BerandaRepositoryInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAlumniVerified
{
    private BerandaRepositoryInterface $berandaRepository;

    public function __construct(BerandaRepositoryInterface $berandaRepository)
    {
        $this->berandaRepository = $berandaRepository;
    }

    /**
     * Ensure the alumni account is fully verified and has completed kuesioner.
     *
     * Full access requires:
     * 1. Admin accepted (status_create === 'ok')
     * 2. Completed all active kuesioner for current career status
     *
     * Profile and kuesioner remain accessible regardless (not behind this middleware).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Autentikasi diperlukan.',
            ], 401);
        }

        $alumni = $user->alumni;

        if (!$alumni) {
            return response()->json([
                'status' => 'error',
                'message' => 'Profil alumni tidak ditemukan.',
            ], 404);
        }

        // Check 1: Admin verification status
        if ($alumni->status_create !== 'ok') {
            $statusMessages = [
                'pending' => 'Akun Anda sedang dalam proses verifikasi. Fitur ini akan tersedia setelah akun disetujui.',
                'rejected' => 'Pengajuan akun Anda ditolak. Silakan perbarui data dan ajukan kembali.',
                'banned' => 'Akun Anda telah diblokir. Hubungi admin untuk informasi lebih lanjut.',
            ];

            return response()->json([
                'status' => 'error',
                'message' => $statusMessages[$alumni->status_create] ?? 'Akun belum diverifikasi.',
                'data' => [
                    'status_create' => $alumni->status_create,
                    'is_verified' => false,
                    'has_completed_kuesioner' => false,
                    'can_access_all' => false,
                ],
            ], 403);
        }

        // Check 2: Kuesioner completion for current career status
        $currentStatusId = $this->berandaRepository->getCurrentStatusId($user->id_users);
        $hasCompletedKuesioner = $this->berandaRepository->hasCompletedKuesioner($user->id_users, $currentStatusId);

        if (!$hasCompletedKuesioner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda harus mengisi kuesioner sesuai status karir saat ini terlebih dahulu untuk mengakses fitur ini.',
                'data' => [
                    'status_create' => $alumni->status_create,
                    'is_verified' => true,
                    'has_completed_kuesioner' => false,
                    'can_access_all' => false,
                ],
            ], 403);
        }

        return $next($request);
    }
}
