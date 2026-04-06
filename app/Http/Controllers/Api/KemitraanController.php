<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KemitraanUniversitasRequest;
use App\Http\Requests\KemitraanPerusahaanRequest;
use App\Http\Resources\KemitraanUniversitasResource;
use App\Http\Resources\KemitraanPerusahaanResource;
use App\Services\KemitraanService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KemitraanController extends Controller
{
    use ApiResponse;

    private KemitraanService $service;

    public function __construct(KemitraanService $service)
    {
        $this->service = $service;
    }

    // ═══════════════════════════════════════════════════════════
    //  MITRA UNIVERSITAS
    // ═══════════════════════════════════════════════════════════

    /**
     * GET /admin/kemitraan/universitas
     *
     * List all universitas with optional ?search= filter.
     */
    public function indexUniversitas(Request $request)
    {
        try {
            $search = $request->query('search');
            $data = $this->service->getAllUniversitas($search);

            return $this->successResponse(
                KemitraanUniversitasResource::collection($data)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data mitra universitas: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/kemitraan/universitas
     *
     * Create a new universitas mitra.
     */
    public function storeUniversitas(KemitraanUniversitasRequest $request)
    {
        try {
            $data = [
                'nama_universitas' => $request->input('nama'),
                'alamat'           => $request->input('jalan'),
            ];

            // Determine logo source (file upload or base64)
            $logoFile = $request->file('logo');
            $logoBase64 = null;

            if (!$logoFile && $request->filled('logo') && str_starts_with($request->input('logo'), 'data:image')) {
                $logoBase64 = $request->input('logo');
            }

            $universitas = $this->service->createUniversitas($data, $logoFile, $logoBase64);

            return $this->createdResponse(
                new KemitraanUniversitasResource($universitas),
                'Universitas berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan universitas: ' . $e->getMessage());
        }
    }

    /**
     * PUT|POST /admin/kemitraan/universitas/{id}
     *
     * Update an existing universitas mitra.
     */
    public function updateUniversitas(KemitraanUniversitasRequest $request, int $id)
    {
        try {
            $data = [
                'nama_universitas' => $request->input('nama'),
                'alamat'           => $request->input('jalan'),
            ];

            $logoFile = $request->file('logo');
            $logoBase64 = null;
            $removeLogo = $request->boolean('remove_logo');

            if (!$logoFile && $request->filled('logo') && str_starts_with($request->input('logo'), 'data:image')) {
                $logoBase64 = $request->input('logo');
            }

            $universitas = $this->service->updateUniversitas($id, $data, $logoFile, $logoBase64, $removeLogo);

            return $this->successResponse(
                new KemitraanUniversitasResource($universitas),
                'Universitas berhasil diperbarui'
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui universitas: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/kemitraan/universitas/{id}
     *
     * Delete a universitas mitra.
     */
    public function destroyUniversitas(int $id)
    {
        try {
            $this->service->deleteUniversitas($id);

            return $this->successResponse(null, 'Universitas berhasil dihapus');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus universitas: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  MITRA PERUSAHAAN
    // ═══════════════════════════════════════════════════════════

    /**
     * GET /admin/kemitraan/perusahaan
     *
     * List all perusahaan with optional ?search= filter.
     */
    public function indexPerusahaan(Request $request)
    {
        try {
            $search = $request->query('search');
            $data = $this->service->getAllPerusahaan($search);

            return $this->successResponse(
                KemitraanPerusahaanResource::collection($data)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data mitra perusahaan: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/kemitraan/perusahaan
     *
     * Create a new perusahaan mitra.
     */
    public function storePerusahaan(KemitraanPerusahaanRequest $request)
    {
        try {
            $data = [
                'nama_perusahaan' => $request->input('nama'),
                'jalan'           => $request->input('jalan'),
            ];

            $logoFile = $request->file('logo');
            $logoBase64 = null;

            if (!$logoFile && $request->filled('logo') && str_starts_with($request->input('logo'), 'data:image')) {
                $logoBase64 = $request->input('logo');
            }

            $perusahaan = $this->service->createPerusahaan($data, $logoFile, $logoBase64);

            return $this->createdResponse(
                new KemitraanPerusahaanResource($perusahaan),
                'Perusahaan berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan perusahaan: ' . $e->getMessage());
        }
    }

    /**
     * PUT|POST /admin/kemitraan/perusahaan/{id}
     *
     * Update an existing perusahaan mitra.
     */
    public function updatePerusahaan(KemitraanPerusahaanRequest $request, int $id)
    {
        try {
            $data = [
                'nama_perusahaan' => $request->input('nama'),
                'jalan'           => $request->input('jalan'),
            ];

            $logoFile = $request->file('logo');
            $logoBase64 = null;
            $removeLogo = $request->boolean('remove_logo');

            if (!$logoFile && $request->filled('logo') && str_starts_with($request->input('logo'), 'data:image')) {
                $logoBase64 = $request->input('logo');
            }

            $perusahaan = $this->service->updatePerusahaan($id, $data, $logoFile, $logoBase64, $removeLogo);

            return $this->successResponse(
                new KemitraanPerusahaanResource($perusahaan),
                'Perusahaan berhasil diperbarui'
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui perusahaan: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/kemitraan/perusahaan/{id}
     *
     * Delete a perusahaan mitra.
     */
    public function destroyPerusahaan(int $id)
    {
        try {
            $this->service->deletePerusahaan($id);

            return $this->successResponse(null, 'Perusahaan berhasil dihapus');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus perusahaan: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EXPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * GET /admin/kemitraan/export?type=universitas|perusahaan&format=csv
     *
     * Export partnership data as CSV.
     */
    public function export(Request $request)
    {
        try {
            $type = $request->query('type', 'universitas');

            if (!in_array($type, ['universitas', 'perusahaan'])) {
                return $this->errorResponse('Tipe export tidak valid. Gunakan: universitas atau perusahaan', 422);
            }

            $exportData = $this->service->getExportData($type);

            $fileName = $type === 'universitas'
                ? 'laporan_mitra_universitas_' . date('Y-m-d') . '.csv'
                : 'laporan_mitra_perusahaan_' . date('Y-m-d') . '.csv';

            return new StreamedResponse(function () use ($exportData) {
                $handle = fopen('php://output', 'w');

                // BOM for UTF-8 Excel compatibility
                fwrite($handle, "\xEF\xBB\xBF");

                // Headers
                fputcsv($handle, $exportData['headers']);

                // Rows
                foreach ($exportData['rows'] as $row) {
                    fputcsv($handle, $row);
                }

                fclose($handle);
            }, 200, [
                'Content-Type'        => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data: ' . $e->getMessage());
        }
    }
}
