<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse($data = null, string $message = 'Berhasil', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function createdResponse($data = null, string $message = 'Data berhasil dibuat'): JsonResponse
    {
        return $this->successResponse($data, $message, 201);
    }

    protected function errorResponse(string $message = 'Terjadi kesalahan', int $code = 500, $errors = null): JsonResponse
    {
        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function notFoundResponse(string $message = 'Data tidak ditemukan'): JsonResponse
    {
        return $this->errorResponse($message, 404);
    }

    protected function unauthorizedResponse(string $message = 'Tidak memiliki akses'): JsonResponse
    {
        return $this->errorResponse($message, 401);
    }

    protected function forbiddenResponse(string $message = 'Akses ditolak'): JsonResponse
    {
        return $this->errorResponse($message, 403);
    }

    protected function validationErrorResponse($errors, string $message = 'Validasi gagal'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors);
    }
}
