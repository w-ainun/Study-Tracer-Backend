<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Services\Alumni\DeskripsiKarierService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DeskripsiKarierController extends Controller
{
    use ApiResponse;

    private DeskripsiKarierService $deskripsiKarierService;

    public function __construct(DeskripsiKarierService $deskripsiKarierService)
    {
        $this->deskripsiKarierService = $deskripsiKarierService;
    }

    /**
     * POST /alumni/deskripsi-karier
     * Create deskripsi karier for a riwayat_status
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_riwayat' => ['required', 'exists:riwayat_status,id_riwayat'],
                'deskripsi' => ['required', 'string', 'min:10'],
            ]);

            $alumniId = $request->user()->alumni->id_alumni;
            $deskripsi = $this->deskripsiKarierService->create($alumniId, $validated);

            return $this->createdResponse($deskripsi, 'Deskripsi karier berhasil ditambahkan');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan deskripsi karier: ' . $e->getMessage());
        }
    }

    /**
     * PUT /alumni/deskripsi-karier/{id}
     * Update deskripsi karier
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'deskripsi' => ['required', 'string', 'min:10'],
            ]);

            $alumniId = $request->user()->alumni->id_alumni;
            $deskripsi = $this->deskripsiKarierService->update($alumniId, $id, $validated);

            return $this->successResponse($deskripsi, 'Deskripsi karier berhasil diperbarui');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui deskripsi karier: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /alumni/deskripsi-karier/{id}
     * Delete deskripsi karier
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $alumniId = $request->user()->alumni->id_alumni;
            $this->deskripsiKarierService->delete($alumniId, $id);

            return $this->successResponse(null, 'Deskripsi karier berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus deskripsi karier: ' . $e->getMessage());
        }
    }
}
