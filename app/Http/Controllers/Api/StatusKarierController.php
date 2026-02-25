<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BidangUsahaResource;
use App\Http\Resources\JurusanKuliahResource;
use App\Http\Resources\PosisiResource;
use App\Http\Resources\ReferensiUniversitasResource;
use App\Services\StatusKarierService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatusKarierController extends Controller
{
    use ApiResponse;

    private StatusKarierService $service;

    public function __construct(StatusKarierService $service)
    {
        $this->service = $service;
    }

    // ═══════════════════════════════════════════════
    //  REFERENSI UNIVERSITAS
    // ═══════════════════════════════════════════════

    public function universitas()
    {
        try {
            $data = $this->service->getAllUniversitas();
            return $this->successResponse(ReferensiUniversitasResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data universitas');
        }
    }

    public function storeUniversitas(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'jurusan' => 'nullable|array',
            'jurusan.*' => 'string|max:255',
        ]);

        try {
            $data = $this->service->createUniversitas($request->only('nama', 'jurusan'));
            return $this->createdResponse(
                new ReferensiUniversitasResource($data),
                'Universitas berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan universitas: ' . $e->getMessage());
        }
    }

    public function updateUniversitas(Request $request, int $id)
    {
        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'jurusan' => 'nullable|array',
            'jurusan.*' => 'string|max:255',
        ]);

        try {
            $data = $this->service->updateUniversitas($id, $request->only('nama', 'jurusan'));
            return $this->successResponse(
                new ReferensiUniversitasResource($data),
                'Universitas berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui universitas: ' . $e->getMessage());
        }
    }

    public function destroyUniversitas(int $id)
    {
        try {
            $this->service->deleteUniversitas($id);
            return $this->successResponse(null, 'Universitas berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus universitas: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  PROGRAM STUDI (JURUSAN KULIAH)
    // ═══════════════════════════════════════════════

    public function prodi()
    {
        try {
            $data = $this->service->getAllProdi();
            return $this->successResponse(JurusanKuliahResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data program studi');
        }
    }

    public function storeProdi(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
        ]);

        try {
            $data = $this->service->createProdi($request->only('nama'));
            return $this->createdResponse(
                new JurusanKuliahResource($data),
                'Program studi berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan program studi: ' . $e->getMessage());
        }
    }

    public function updateProdi(Request $request, int $id)
    {
        $request->validate([
            'nama' => 'sometimes|string|max:255',
        ]);

        try {
            $data = $this->service->updateProdi($id, $request->only('nama'));
            return $this->successResponse(
                new JurusanKuliahResource($data),
                'Program studi berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui program studi: ' . $e->getMessage());
        }
    }

    public function destroyProdi(int $id)
    {
        try {
            $this->service->deleteProdi($id);
            return $this->successResponse(null, 'Program studi berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus program studi: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  BIDANG WIRAUSAHA
    // ═══════════════════════════════════════════════

    public function bidangUsaha()
    {
        try {
            $data = $this->service->getAllBidangUsaha();
            return $this->successResponse(BidangUsahaResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data bidang usaha');
        }
    }

    public function storeBidangUsaha(Request $request)
    {
        $request->validate([
            'nama_bidang' => 'required|string|max:255',
        ]);

        try {
            $data = $this->service->createBidangUsaha($request->only('nama_bidang'));
            return $this->createdResponse(
                new BidangUsahaResource($data),
                'Bidang usaha berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan bidang usaha: ' . $e->getMessage());
        }
    }

    public function updateBidangUsaha(Request $request, int $id)
    {
        $request->validate([
            'nama_bidang' => 'sometimes|string|max:255',
        ]);

        try {
            $data = $this->service->updateBidangUsaha($id, $request->only('nama_bidang'));
            return $this->successResponse(
                new BidangUsahaResource($data),
                'Bidang usaha berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui bidang usaha: ' . $e->getMessage());
        }
    }

    public function destroyBidangUsaha(int $id)
    {
        try {
            $this->service->deleteBidangUsaha($id);
            return $this->successResponse(null, 'Bidang usaha berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus bidang usaha: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  POSISI PEKERJAAN
    // ═══════════════════════════════════════════════

    public function posisi()
    {
        try {
            $data = $this->service->getAllPosisi();
            return $this->successResponse(PosisiResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data posisi');
        }
    }

    public function storePosisi(Request $request)
    {
        $request->validate([
            'nama_posisi' => 'required|string|max:255',
        ]);

        try {
            $data = $this->service->createPosisi($request->only('nama_posisi'));
            return $this->createdResponse(
                new PosisiResource($data),
                'Posisi berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan posisi: ' . $e->getMessage());
        }
    }

    public function updatePosisi(Request $request, int $id)
    {
        $request->validate([
            'nama_posisi' => 'sometimes|string|max:255',
        ]);

        try {
            $data = $this->service->updatePosisi($id, $request->only('nama_posisi'));
            return $this->successResponse(
                new PosisiResource($data),
                'Posisi berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui posisi: ' . $e->getMessage());
        }
    }

    public function destroyPosisi(int $id)
    {
        try {
            $this->service->deletePosisi($id);
            return $this->successResponse(null, 'Posisi berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus posisi: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  REPORT / EXPORT
    // ═══════════════════════════════════════════════

    public function statusDistribution()
    {
        try {
            $data = $this->service->getStatusDistribution();
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil distribusi status');
        }
    }

    public function exportReport(Request $request): StreamedResponse
    {
        $request->validate([
            'type' => 'required|in:universitas,prodi,wirausaha,posisi',
            'format' => 'sometimes|in:csv,pdf',
        ]);

        $type = $request->input('type');
        $data = $this->service->exportStatusReport($type);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="status_karier_' . $type . '_' . now()->format('Ymd_His') . '.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($data, $type) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            // Header row
            if ($type === 'universitas') {
                fputcsv($handle, ['ID', 'Nama Universitas', 'Jurusan']);
            } else {
                fputcsv($handle, ['ID', 'Nama']);
            }

            // Data rows
            foreach ($data as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
