<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenExpiration
{
    /**
     * Cek apakah token Sanctum sudah expired berdasarkan last_used_at.
     * Default: 5 jam (300 menit) inaktif → token dihapus → user harus login ulang.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->currentAccessToken()) {
            return $next($request);
        }

        $token = $user->currentAccessToken();

        // Cek apakah token memiliki last_used_at
        if ($token->last_used_at) {
            $expirationMinutes = (int) config('sanctum.expiration', 300);
            $lastUsed = $token->last_used_at;
            $expiredAt = $lastUsed->addMinutes($expirationMinutes);

            if (now()->greaterThan($expiredAt)) {
                // Token sudah expired karena inaktif terlalu lama
                $token->delete();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Sesi Anda telah berakhir karena tidak aktif selama lebih dari 5 jam. Silakan login kembali.',
                    'error_code' => 'TOKEN_EXPIRED_INACTIVE',
                ], 401);
            }
        }

        return $next($request);
    }
}
