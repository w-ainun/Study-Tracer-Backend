<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BidangUsahaResource;
use App\Http\Resources\JurusanKuliahResource;
use App\Http\Resources\JurusanResource;
use App\Http\Resources\KotaResource;
use App\Http\Resources\PerusahaanResource;
use App\Http\Resources\ProvinsiResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\SocialMediaResource;
use App\Http\Resources\StatusResource;
use App\Services\MasterDataService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    use ApiResponse;

    private MasterDataService $masterDataService;

    public function __construct(MasterDataService $masterDataService)
    {
        $this->masterDataService = $masterDataService;
    }

    // =====================
    // PROVINSI
    // =====================
    public function provinsi()
    {
        try {
            $data = $this->masterDataService->getAllProvinsi();
            return $this->successResponse(ProvinsiResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data provinsi');
        }
    }

    public function showProvinsi(int $id)
    {
        try {
            $data = $this->masterDataService->getProvinsiById($id);
            return $this->successResponse(new ProvinsiResource($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Provinsi tidak ditemukan', 404);
        }
    }

    public function storeProvinsi(Request $request)
    {
        $request->validate(['nama_provinsi' => 'required|string|max:255|unique:provinsi,nama_provinsi']);
        try {
            $data = $this->masterDataService->createProvinsi($request->only('nama_provinsi'));
            return $this->createdResponse(new ProvinsiResource($data), 'Provinsi berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan provinsi: ' . $e->getMessage());
        }
    }

    public function updateProvinsi(Request $request, int $id)
    {
        $request->validate(['nama_provinsi' => 'required|string|max:255']);
        try {
            $data = $this->masterDataService->updateProvinsi($id, $request->only('nama_provinsi'));
            return $this->successResponse(new ProvinsiResource($data), 'Provinsi berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui provinsi: ' . $e->getMessage());
        }
    }

    public function destroyProvinsi(int $id)
    {
        try {
            $this->masterDataService->deleteProvinsi($id);
            return $this->successResponse(null, 'Provinsi berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus provinsi: ' . $e->getMessage());
        }
    }

    // =====================
    // KOTA
    // =====================
    public function kota(Request $request)
    {
        try {
            $provinsiId = $request->input('id_provinsi');
            $data = $provinsiId
                ? $this->masterDataService->getKotaByProvinsi($provinsiId)
                : $this->masterDataService->getAllKota();
            return $this->successResponse(KotaResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data kota');
        }
    }

    public function storeKota(Request $request)
    {
        $request->validate([
            'nama_kota' => 'required|string|max:255',
            'id_provinsi' => 'required|exists:provinsi,id_provinsi',
        ]);
        try {
            $data = $this->masterDataService->createKota($request->only('nama_kota', 'id_provinsi'));
            return $this->createdResponse(new KotaResource($data), 'Kota berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan kota: ' . $e->getMessage());
        }
    }

    public function updateKota(Request $request, int $id)
    {
        $request->validate([
            'nama_kota' => 'required|string|max:255',
            'id_provinsi' => 'sometimes|exists:provinsi,id_provinsi',
        ]);
        try {
            $data = $this->masterDataService->updateKota($id, $request->only('nama_kota', 'id_provinsi'));
            return $this->successResponse(new KotaResource($data), 'Kota berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui kota: ' . $e->getMessage());
        }
    }

    public function destroyKota(int $id)
    {
        try {
            $this->masterDataService->deleteKota($id);
            return $this->successResponse(null, 'Kota berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus kota: ' . $e->getMessage());
        }
    }

    // =====================
    // JURUSAN (SMK)
    // =====================
    public function jurusan()
    {
        try {
            $data = $this->masterDataService->getAllJurusan();
            return $this->successResponse(JurusanResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data jurusan');
        }
    }

    public function storeJurusan(Request $request)
    {
        $request->validate(['nama_jurusan' => 'required|string|max:255|unique:jurusan,nama_jurusan']);
        try {
            $data = $this->masterDataService->createJurusan($request->only('nama_jurusan'));
            return $this->createdResponse(new JurusanResource($data), 'Jurusan berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan jurusan: ' . $e->getMessage());
        }
    }

    public function updateJurusan(Request $request, int $id)
    {
        $request->validate(['nama_jurusan' => 'required|string|max:255']);
        try {
            $data = $this->masterDataService->updateJurusan($id, $request->only('nama_jurusan'));
            return $this->successResponse(new JurusanResource($data), 'Jurusan berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui jurusan: ' . $e->getMessage());
        }
    }

    public function destroyJurusan(int $id)
    {
        try {
            $this->masterDataService->deleteJurusan($id);
            return $this->successResponse(null, 'Jurusan berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus jurusan: ' . $e->getMessage());
        }
    }

    // =====================
    // JURUSAN KULIAH
    // =====================
    public function jurusanKuliah()
    {
        try {
            $data = $this->masterDataService->getAllJurusanKuliah();
            return $this->successResponse(JurusanKuliahResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data jurusan kuliah');
        }
    }

    public function storeJurusanKuliah(Request $request)
    {
        $request->validate(['nama_jurusan_kuliah' => 'required|string|max:255|unique:jurusan_kuliah,nama_jurusan_kuliah']);
        try {
            $data = $this->masterDataService->createJurusanKuliah($request->only('nama_jurusan_kuliah'));
            return $this->createdResponse(new JurusanKuliahResource($data), 'Jurusan kuliah berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan jurusan kuliah: ' . $e->getMessage());
        }
    }

    public function updateJurusanKuliah(Request $request, int $id)
    {
        $request->validate(['nama_jurusan_kuliah' => 'required|string|max:255']);
        try {
            $data = $this->masterDataService->updateJurusanKuliah($id, $request->only('nama_jurusan_kuliah'));
            return $this->successResponse(new JurusanKuliahResource($data), 'Jurusan kuliah berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui jurusan kuliah: ' . $e->getMessage());
        }
    }

    public function destroyJurusanKuliah(int $id)
    {
        try {
            $this->masterDataService->deleteJurusanKuliah($id);
            return $this->successResponse(null, 'Jurusan kuliah berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus jurusan kuliah: ' . $e->getMessage());
        }
    }

    // =====================
    // SKILLS
    // =====================
    public function skills()
    {
        try {
            $data = $this->masterDataService->getAllSkills();
            return $this->successResponse(SkillResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data skill');
        }
    }

    public function storeSkill(Request $request)
    {
        $request->validate(['name_skills' => 'required|string|max:255|unique:skills,name_skills']);
        try {
            $data = $this->masterDataService->createSkill($request->only('name_skills'));
            return $this->createdResponse(new SkillResource($data), 'Skill berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan skill: ' . $e->getMessage());
        }
    }

    public function updateSkill(Request $request, int $id)
    {
        $request->validate(['name_skills' => 'required|string|max:255']);
        try {
            $data = $this->masterDataService->updateSkill($id, $request->only('name_skills'));
            return $this->successResponse(new SkillResource($data), 'Skill berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui skill: ' . $e->getMessage());
        }
    }

    public function destroySkill(int $id)
    {
        try {
            $this->masterDataService->deleteSkill($id);
            return $this->successResponse(null, 'Skill berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus skill: ' . $e->getMessage());
        }
    }

    // =====================
    // SOCIAL MEDIA
    // =====================
    public function socialMedia()
    {
        try {
            $data = $this->masterDataService->getAllSocialMedia();
            return $this->successResponse(SocialMediaResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data social media');
        }
    }

    public function storeSocialMedia(Request $request)
    {
        $request->validate([
            'nama_sosmed' => 'required|string|max:255|unique:social_media,nama_sosmed',
            'icon_sosmed' => 'nullable|string|max:255',
        ]);
        try {
            $data = $this->masterDataService->createSocialMedia($request->only('nama_sosmed', 'icon_sosmed'));
            return $this->createdResponse(new SocialMediaResource($data), 'Social media berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan social media: ' . $e->getMessage());
        }
    }

    public function updateSocialMedia(Request $request, int $id)
    {
        $request->validate([
            'nama_sosmed' => 'required|string|max:255',
            'icon_sosmed' => 'nullable|string|max:255',
        ]);
        try {
            $data = $this->masterDataService->updateSocialMedia($id, $request->only('nama_sosmed', 'icon_sosmed'));
            return $this->successResponse(new SocialMediaResource($data), 'Social media berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui social media: ' . $e->getMessage());
        }
    }

    public function destroySocialMedia(int $id)
    {
        try {
            $this->masterDataService->deleteSocialMedia($id);
            return $this->successResponse(null, 'Social media berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus social media: ' . $e->getMessage());
        }
    }

    // =====================
    // STATUS
    // =====================
    public function status()
    {
        try {
            $data = $this->masterDataService->getAllStatus();
            return $this->successResponse(StatusResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data status');
        }
    }

    public function storeStatus(Request $request)
    {
        $request->validate(['nama_status' => 'required|string|max:255|unique:status,nama_status']);
        try {
            $data = $this->masterDataService->createStatus($request->only('nama_status'));
            return $this->createdResponse(new StatusResource($data), 'Status berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan status: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $request->validate(['nama_status' => 'required|string|max:255']);
        try {
            $data = $this->masterDataService->updateStatus($id, $request->only('nama_status'));
            return $this->successResponse(new StatusResource($data), 'Status berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui status: ' . $e->getMessage());
        }
    }

    public function destroyStatus(int $id)
    {
        try {
            $this->masterDataService->deleteStatus($id);
            return $this->successResponse(null, 'Status berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus status: ' . $e->getMessage());
        }
    }

    // =====================
    // BIDANG USAHA
    // =====================
    public function bidangUsaha()
    {
        try {
            $data = $this->masterDataService->getAllBidangUsaha();
            return $this->successResponse(BidangUsahaResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data bidang usaha');
        }
    }

    public function storeBidangUsaha(Request $request)
    {
        $request->validate(['nama_bidang' => 'required|string|max:255|unique:bidang_usaha,nama_bidang']);
        try {
            $data = $this->masterDataService->createBidangUsaha($request->only('nama_bidang'));
            return $this->createdResponse(new BidangUsahaResource($data), 'Bidang usaha berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan bidang usaha: ' . $e->getMessage());
        }
    }

    public function updateBidangUsaha(Request $request, int $id)
    {
        $request->validate(['nama_bidang' => 'required|string|max:255']);
        try {
            $data = $this->masterDataService->updateBidangUsaha($id, $request->only('nama_bidang'));
            return $this->successResponse(new BidangUsahaResource($data), 'Bidang usaha berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui bidang usaha: ' . $e->getMessage());
        }
    }

    public function destroyBidangUsaha(int $id)
    {
        try {
            $this->masterDataService->deleteBidangUsaha($id);
            return $this->successResponse(null, 'Bidang usaha berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus bidang usaha: ' . $e->getMessage());
        }
    }

    // =====================
    // PERUSAHAAN
    // =====================
    public function perusahaan(Request $request)
    {
        try {
            $filters = $request->only(['search', 'id_kota']);
            $perPage = $request->input('per_page', 15);
            $data = $this->masterDataService->getAllPerusahaan($filters, $perPage);
            return $this->successResponse(PerusahaanResource::collection($data)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data perusahaan');
        }
    }

    public function storePerusahaan(Request $request)
    {
        $request->validate([
            'nama_perusahaan' => 'required|string|max:255',
            'id_bidang_usaha' => 'required|exists:bidang_usaha,id_bidang_usaha',
            'id_kota' => 'required|exists:kota,id_kota',
            'alamat_perusahaan' => 'nullable|string',
        ]);
        try {
            $data = $this->masterDataService->createPerusahaan(
                $request->only('nama_perusahaan', 'id_bidang_usaha', 'id_kota', 'alamat_perusahaan')
            );
            return $this->createdResponse(new PerusahaanResource($data), 'Perusahaan berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan perusahaan: ' . $e->getMessage());
        }
    }

    public function updatePerusahaan(Request $request, int $id)
    {
        $request->validate([
            'nama_perusahaan' => 'sometimes|string|max:255',
            'id_bidang_usaha' => 'sometimes|exists:bidang_usaha,id_bidang_usaha',
            'id_kota' => 'sometimes|exists:kota,id_kota',
            'alamat_perusahaan' => 'nullable|string',
        ]);
        try {
            $data = $this->masterDataService->updatePerusahaan(
                $id,
                $request->only('nama_perusahaan', 'id_bidang_usaha', 'id_kota', 'alamat_perusahaan')
            );
            return $this->successResponse(new PerusahaanResource($data), 'Perusahaan berhasil diperbarui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui perusahaan: ' . $e->getMessage());
        }
    }

    public function destroyPerusahaan(int $id)
    {
        try {
            $this->masterDataService->deletePerusahaan($id);
            return $this->successResponse(null, 'Perusahaan berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus perusahaan: ' . $e->getMessage());
        }
    }

    // =====================
    // UNIVERSITAS (read from existing)
    // =====================
    public function universitas(Request $request)
    {
        try {
            $data = $this->masterDataService->getAllUniversitas();
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data universitas');
        }
    }

    public function storeUniversitas(Request $request)
    {
        $request->validate([
            'nama_universitas' => 'required|string|max:255',
        ]);
        try {
            $data = $this->masterDataService->createUniversitas($request->only('nama_universitas'));
            return $this->createdResponse($data, 'Universitas berhasil ditambahkan');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan universitas: ' . $e->getMessage());
        }
    }

    // =====================
    // TIPE PEKERJAAN (dynamic list from Lowongan)
    // =====================
    public function tipePekerjaan()
    {
        try {
            $data = \App\Models\Lowongan::whereNotNull('tipe_pekerjaan')
                ->distinct()
                ->orderBy('tipe_pekerjaan')
                ->pluck('tipe_pekerjaan')
                ->map(fn ($item) => ['nama' => $item]);
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data tipe pekerjaan');
        }
    }
}
