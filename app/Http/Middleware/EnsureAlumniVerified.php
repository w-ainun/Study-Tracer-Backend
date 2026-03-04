<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAlumniVerified
{
    /**
     * Ensure the alumni account has been verified (status_create === 'ok').
     * Blocks access to restricted features (lowongan, jejaring alumni) for unverified alumni.
     * Profile and kuesioner remain accessible regardless of status.
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
                ],
            ], 403);
        }

        return $next($request);
    }
}
