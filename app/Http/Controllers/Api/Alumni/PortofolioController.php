<?php

namespace App\Http\Controllers\Api\Alumni;

use App\Http\Controllers\Controller;
use App\Services\Alumni\PortofolioService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PortofolioController extends Controller
{
    use ApiResponse;

    private PortofolioService $portofolioService;

    public function __construct(PortofolioService $portofolioService)
    {
        $this->portofolioService = $portofolioService;
    }

    /**
     * POST /alumni/portofolio
     * Create portofolio
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'judul' => ['required', 'string', 'max:255'],
                'deskripsi' => ['nullable', 'string'],
                'link_project' => ['nullable', 'url', 'max:500'],
                'gambar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB
            ]);

            $alumniId = $request->user()->alumni->id_alumni;
            $gambar = $request->hasFile('gambar') ? $request->file('gambar') : null;

            $portofolio = $this->portofolioService->create($alumniId, $validated, $gambar);

            return $this->createdResponse($portofolio, 'Portofolio berhasil ditambahkan');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan portofolio: ' . $e->getMessage());
        }
    }

    /**
     * PUT/POST /alumni/portofolio/{id}
     * Update portofolio
     */
    public function update(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'judul' => ['sometimes', 'required', 'string', 'max:255'],
                'deskripsi' => ['nullable', 'string'],
                'link_project' => ['nullable', 'url', 'max:500'],
                'gambar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            ]);

            $alumniId = $request->user()->alumni->id_alumni;
            $gambar = $request->hasFile('gambar') ? $request->file('gambar') : null;

            $portofolio = $this->portofolioService->update($alumniId, $id, $validated, $gambar);

            return $this->successResponse($portofolio, 'Portofolio berhasil diperbarui');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui portofolio: ' . $e->getMessage());
        }
    }

    /**
     * DELETE /alumni/portofolio/{id}
     * Delete portofolio
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $alumniId = $request->user()->alumni->id_alumni;
            $this->portofolioService->delete($alumniId, $id);

            return $this->successResponse(null, 'Portofolio berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus portofolio: ' . $e->getMessage());
        }
    }
}
