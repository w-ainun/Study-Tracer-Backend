<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Services\LamaranService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LamaranController extends Controller
{
    use ApiResponse;

    private LamaranService $lamaranService;

    public function __construct(LamaranService $lamaranService)
    {
        $this->lamaranService = $lamaranService;
    }

    /**
     * POST /alumni/lamaran/{lowonganId}
     * Alumni applies to a lowongan.
     */
    public function apply(Request $request, int $lowonganId)
    {
        try {
            $alumni = $request->user()->alumni;
            if (!$alumni) {
                return $this->errorResponse('Data alumni tidak ditemukan', 404);
            }

            $catatan = $request->input('catatan');
            $lamaran = $this->lamaranService->apply($alumni->id_alumni, $lowonganId, $catatan);

            return $this->createdResponse([
                'id_lamaran' => $lamaran->id_lamaran,
                'id_lowongan' => $lamaran->id_lowongan,
                'status' => $lamaran->status,
                'tanggal_apply' => $lamaran->tanggal_apply,
                'lowongan' => [
                    'judul_lowongan' => $lamaran->lowongan?->judul_lowongan,
                    'perusahaan' => $lamaran->lowongan?->perusahaan?->nama_perusahaan,
                ],
            ], 'Lamaran berhasil diajukan');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /alumni/lamaran
     * Alumni's application history with filter support.
     */
    public function index(Request $request)
    {
        try {
            $alumni = $request->user()->alumni;
            if (!$alumni) {
                return $this->errorResponse('Data alumni tidak ditemukan', 404);
            }

            $filters = $request->only(['status', 'search']);
            $perPage = $request->input('per_page', 15);

            $data = $this->lamaranService->getAlumniHistory($alumni->id_alumni, $filters, $perPage);

            $result = $data->through(function ($lamaran) {
                return [
                    'id_lamaran' => $lamaran->id_lamaran,
                    'status' => $lamaran->status,
                    'tanggal_apply' => $lamaran->tanggal_apply?->toISOString(),
                    'tanggal_respon' => $lamaran->tanggal_respon?->toISOString(),
                    'catatan' => $lamaran->catatan,
                    'catatan_admin' => $lamaran->catatan_admin,
                    'lowongan' => $lamaran->lowongan ? [
                        'id_lowongan' => $lamaran->lowongan->id_lowongan,
                        'judul_lowongan' => $lamaran->lowongan->judul_lowongan,
                        'tipe_pekerjaan' => $lamaran->lowongan->tipe_pekerjaan,
                        'status' => $lamaran->lowongan->status,
                        'foto_lowongan' => $lamaran->lowongan->foto_lowongan
                            ? asset('storage/' . $lamaran->lowongan->foto_lowongan)
                            : null,
                        'perusahaan' => $lamaran->lowongan->perusahaan ? [
                            'nama_perusahaan' => $lamaran->lowongan->perusahaan->nama_perusahaan,
                            'kota' => $lamaran->lowongan->perusahaan->kota?->nama_kota ?? null,
                            'provinsi' => $lamaran->lowongan->perusahaan->kota?->provinsi?->nama_provinsi ?? null,
                        ] : null,
                        'skills' => $lamaran->lowongan->skills?->map(fn($s) => [
                            'id_skills' => $s->id_skills,
                            'nama_skills' => $s->nama_skills,
                        ]),
                    ] : null,
                ];
            });

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil riwayat lamaran: ' . $e->getMessage());
        }
    }

    /**
     * GET /alumni/lamaran/stats
     * Alumni's application statistics.
     */
    public function stats(Request $request)
    {
        try {
            $alumni = $request->user()->alumni;
            if (!$alumni) {
                return $this->errorResponse('Data alumni tidak ditemukan', 404);
            }

            $stats = $this->lamaranService->getAlumniStats($alumni->id_alumni);
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik lamaran: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /alumni/lamaran/{id}
     * Cancel a pending application.
     */
    public function cancel(Request $request, int $id)
    {
        try {
            $alumni = $request->user()->alumni;
            if (!$alumni) {
                return $this->errorResponse('Data alumni tidak ditemukan', 404);
            }

            $this->lamaranService->cancel($id, $alumni->id_alumni);
            return $this->successResponse(null, 'Lamaran berhasil dibatalkan');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
