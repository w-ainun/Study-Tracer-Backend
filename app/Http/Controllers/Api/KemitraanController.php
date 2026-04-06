<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\KemitraanRequest;
use App\Http\Resources\KemitraanResource;
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
     */
    public function indexUniversitas(Request $request)
    {
        try {
            $data = $this->service->getAll('universitas', $request->query('search'));

            return $this->successResponse(
                KemitraanResource::collection($data)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data mitra universitas: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/kemitraan/universitas
     */
    public function storeUniversitas(KemitraanRequest $request)
    {
        try {
            $data = [
                'tipe'            => 'universitas',
                'nama'            => $request->input('nama'),
                'alamat'          => $request->input('jalan'),
                'id_universitas'  => $request->input('id_universitas'),
            ];

            [$logoFile, $logoBase64] = $this->extractLogo($request);

            $kemitraan = $this->service->create($data, $logoFile, $logoBase64);

            return $this->createdResponse(
                new KemitraanResource($kemitraan),
                'Mitra universitas berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan mitra universitas: ' . $e->getMessage());
        }
    }

    /**
     * PUT|POST /admin/kemitraan/universitas/{id}
     */
    public function updateUniversitas(KemitraanRequest $request, int $id)
    {
        try {
            $data = [
                'nama'            => $request->input('nama'),
                'alamat'          => $request->input('jalan'),
                'id_universitas'  => $request->input('id_universitas'),
            ];

            [$logoFile, $logoBase64] = $this->extractLogo($request);
            $removeLogo = $request->boolean('remove_logo');

            $kemitraan = $this->service->update($id, $data, $logoFile, $logoBase64, $removeLogo);

            return $this->successResponse(
                new KemitraanResource($kemitraan),
                'Mitra universitas berhasil diperbarui'
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui mitra universitas: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/kemitraan/universitas/{id}
     */
    public function destroyUniversitas(int $id)
    {
        try {
            $this->service->delete($id);

            return $this->successResponse(null, 'Mitra universitas berhasil dihapus');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus mitra universitas: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  MITRA PERUSAHAAN
    // ═══════════════════════════════════════════════════════════

    /**
     * GET /admin/kemitraan/perusahaan
     */
    public function indexPerusahaan(Request $request)
    {
        try {
            $data = $this->service->getAll('perusahaan', $request->query('search'));

            return $this->successResponse(
                KemitraanResource::collection($data)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data mitra perusahaan: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/kemitraan/perusahaan
     */
    public function storePerusahaan(KemitraanRequest $request)
    {
        try {
            $data = [
                'tipe'           => 'perusahaan',
                'nama'           => $request->input('nama'),
                'alamat'         => $request->input('jalan'),
                'id_perusahaan'  => $request->input('id_perusahaan'),
            ];

            [$logoFile, $logoBase64] = $this->extractLogo($request);

            $kemitraan = $this->service->create($data, $logoFile, $logoBase64);

            return $this->createdResponse(
                new KemitraanResource($kemitraan),
                'Mitra perusahaan berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan mitra perusahaan: ' . $e->getMessage());
        }
    }

    /**
     * PUT|POST /admin/kemitraan/perusahaan/{id}
     */
    public function updatePerusahaan(KemitraanRequest $request, int $id)
    {
        try {
            $data = [
                'nama'           => $request->input('nama'),
                'alamat'         => $request->input('jalan'),
                'id_perusahaan'  => $request->input('id_perusahaan'),
            ];

            [$logoFile, $logoBase64] = $this->extractLogo($request);
            $removeLogo = $request->boolean('remove_logo');

            $kemitraan = $this->service->update($id, $data, $logoFile, $logoBase64, $removeLogo);

            return $this->successResponse(
                new KemitraanResource($kemitraan),
                'Mitra perusahaan berhasil diperbarui'
            );
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui mitra perusahaan: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/kemitraan/perusahaan/{id}
     */
    public function destroyPerusahaan(int $id)
    {
        try {
            $this->service->delete($id);

            return $this->successResponse(null, 'Mitra perusahaan berhasil dihapus');
        } catch (ModelNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus mitra perusahaan: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EXPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * GET /admin/kemitraan/export?type=universitas|perusahaan
     */
    public function export(Request $request)
    {
        try {
            $type = $request->query('type', 'universitas');

            if (!in_array($type, ['universitas', 'perusahaan'])) {
                return $this->errorResponse('Tipe export tidak valid. Gunakan: universitas atau perusahaan', 422);
            }

            $exportData = $this->service->getExportData($type);

            $fileName = 'laporan_mitra_' . $type . '_' . date('Y-m-d') . '.csv';

            return new StreamedResponse(function () use ($exportData) {
                $handle = fopen('php://output', 'w');

                // BOM for UTF-8 Excel compatibility
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv($handle, $exportData['headers']);

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

    // ═══════════════════════════════════════════════════════════
    //  PRIVATE HELPER
    // ═══════════════════════════════════════════════════════════

    /**
     * Extract logo from request — either as file upload or base64 data URL.
     *
     * @return array{0: \Illuminate\Http\UploadedFile|null, 1: string|null}
     */
    private function extractLogo(Request $request): array
    {
        $logoFile = $request->file('logo');
        $logoBase64 = null;

        if (!$logoFile && $request->filled('logo') && str_starts_with($request->input('logo'), 'data:image')) {
            $logoBase64 = $request->input('logo');
        }

        return [$logoFile, $logoBase64];
    }
}
