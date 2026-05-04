<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportKelulusanRequest;
use App\Http\Requests\StoreCalonLulusanRequest;
use App\Http\Resources\CalonLulusanResource;
use App\Http\Resources\RiwayatKelulusanResource;
use App\Models\RiwayatKelulusan;
use App\Services\KelulusanService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KelulusanController extends Controller
{
    use ApiResponse;

    private KelulusanService $kelulusanService;

    public function __construct(KelulusanService $kelulusanService)
    {
        $this->kelulusanService = $kelulusanService;
    }

    // ═══════════════════════════════════════════════
    //  CALON LULUSAN (STAGING TABLE)
    // ═══════════════════════════════════════════════

    /**
     * GET /admin/kelulusan/calon
     * List all calon lulusan with search & filter.
     */
    public function indexCalon(Request $request)
    {
        try {
            $filters = $request->only(['search', 'jurusan', 'id_jurusan']);
            $perPage = $request->input('per_page', 50);
            $calon = $this->kelulusanService->getCalonLulusan($filters, $perPage);

            return $this->successResponse(
                CalonLulusanResource::collection($calon)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data calon lulusan: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/kelulusan/calon
     * Create a single calon lulusan manually.
     */
    public function storeCalon(StoreCalonLulusanRequest $request)
    {
        try {
            $data = $request->only(['nisn', 'nama', 'id_jurusan']);
            $data['imported_by'] = $request->user()->id_users;

            $calon = $this->kelulusanService->createCalonLulusan($data);

            return $this->createdResponse(
                new CalonLulusanResource($calon),
                'Calon lulusan berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan calon lulusan: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/kelulusan/calon/{id}
     * Delete a single calon lulusan.
     */
    public function destroyCalon(int $id)
    {
        try {
            $this->kelulusanService->deleteCalonLulusan($id);
            return $this->successResponse(null, 'Calon lulusan berhasil dihapus');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFoundResponse('Calon lulusan tidak ditemukan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus calon lulusan: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /admin/kelulusan/calon
     * Clear all calon lulusan (empty staging).
     */
    public function clearCalon()
    {
        try {
            $count = $this->kelulusanService->clearCalonLulusan();
            return $this->successResponse(
                ['deleted' => $count],
                "Berhasil menghapus {$count} data calon lulusan"
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengosongkan data calon lulusan: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  IMPORT EXCEL
    // ═══════════════════════════════════════════════

    /**
     * POST /admin/kelulusan/import
     * Import calon lulusan from Excel/CSV file.
     */
    public function import(ImportKelulusanRequest $request)
    {
        try {
            $file = $request->file('file');
            $adminUserId = $request->user()->id_users;

            $result = $this->kelulusanService->importFromExcel($file, $adminUserId);

            $message = "Berhasil mengimpor {$result['inserted']} dari {$result['total_rows']} data";
            if ($result['skipped'] > 0) {
                $message .= " ({$result['skipped']} dilewati)";
            }

            return $this->successResponse($result, $message);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validasi file gagal');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengimpor file: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  SIMPAN KELULUSAN (CONFIRM GRADUATION)
    // ═══════════════════════════════════════════════

    /**
     * POST /admin/kelulusan/simpan
     * Confirm graduation — move all calon → riwayat kelulusan.
     */
    public function simpanKelulusan(Request $request)
    {
        try {
            $adminUserId = $request->user()->id_users;
            $tahunLulus = $request->input('tahun_lulus');

            $result = $this->kelulusanService->simpanKelulusan(
                $adminUserId,
                $tahunLulus ? (int) $tahunLulus : null
            );

            return $this->successResponse(
                $result,
                "Berhasil menetapkan kelulusan untuk {$result['total']} siswa pada tahun {$result['tahun_lulus']}"
            );
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validasi kelulusan gagal');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan kelulusan: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  RIWAYAT KELULUSAN (CONFIRMED GRADUATES)
    // ═══════════════════════════════════════════════

    /**
     * GET /admin/kelulusan/riwayat
     * List all confirmed graduates with search & filter.
     */
    public function indexRiwayat(Request $request)
    {
        try {
            $filters = $request->only(['search', 'jurusan', 'id_jurusan', 'tahun_lulus', 'tahun']);
            $perPage = $request->input('per_page', 15);
            $riwayat = $this->kelulusanService->getRiwayatKelulusan($filters, $perPage);

            return $this->successResponse(
                RiwayatKelulusanResource::collection($riwayat)->response()->getData(true)
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil riwayat kelulusan: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  STATISTICS & FILTERS
    // ═══════════════════════════════════════════════

    /**
     * GET /admin/kelulusan/stats
     * Get kelulusan statistics.
     */
    public function stats()
    {
        try {
            $stats = $this->kelulusanService->getStats();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik kelulusan');
        }
    }

    /**
     * GET /admin/kelulusan/filters
     * Get available filter options (tahun_lulus list).
     */
    public function filters()
    {
        try {
            $tahunLulus = $this->kelulusanService->getDistinctTahunLulus();
            return $this->successResponse([
                'tahun_lulus' => $tahunLulus,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil filter kelulusan');
        }
    }

    // ═══════════════════════════════════════════════
    //  EXPORT
    // ═══════════════════════════════════════════════

    /**
     * GET /admin/kelulusan/export
     * Export riwayat kelulusan as CSV (streamed).
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'jurusan', 'id_jurusan', 'tahun_lulus', 'tahun']);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Data_Riwayat_Kelulusan_' . now()->format('Ymd_His') . '.csv"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($filters) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'No',
                'NISN',
                'Nama Siswa',
                'Jurusan',
                'Tahun Lulus',
                'Tanggal Dikonfirmasi',
            ]);

            $counter = 0;

            $this->kelulusanService->streamRiwayatKelulusan($filters, function ($chunk) use ($handle, &$counter) {
                foreach ($chunk as $item) {
                    $counter++;
                    fputcsv($handle, [
                        $counter,
                        $item->nisn,
                        $item->nama,
                        $item->jurusan?->nama_jurusan ?? '-',
                        $item->tahun_lulus,
                        $item->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ═══════════════════════════════════════════════
    //  CEK KELULUSAN (ALUMNI SELF-CHECK)
    // ═══════════════════════════════════════════════

    /**
     * GET /alumni/kelulusan/cek
     * Alumni checks their own graduation status by NISN.
     */
    public function cekKelulusan(Request $request)
    {
        try {
            $user = $request->user();
            $alumni = $user->alumni;

            if (!$alumni || !$alumni->nisn) {
                return $this->errorResponse('Data alumni tidak ditemukan atau NISN belum diisi.', 404);
            }

            $riwayat = RiwayatKelulusan::with('jurusan')
                ->where('nisn', $alumni->nisn)
                ->latest()
                ->first();

            if (!$riwayat) {
                return $this->successResponse([
                    'is_lulus' => false,
                    'message'  => 'Data kelulusan Anda belum tersedia. Silakan hubungi pihak sekolah untuk informasi lebih lanjut.',
                ], 'Data kelulusan belum ditemukan');
            }

            return $this->successResponse([
                'is_lulus'    => true,
                'data'        => new RiwayatKelulusanResource($riwayat),
                'alumni_nama' => $alumni->nama_alumni,
                'alumni_nisn' => $alumni->nisn,
                'alumni_jurusan' => $alumni->jurusan?->nama_jurusan ?? '-',
            ], 'Data kelulusan ditemukan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengecek kelulusan: ' . $e->getMessage());
        }
    }
}
