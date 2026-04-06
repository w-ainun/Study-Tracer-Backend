<?php

namespace App\Services;

use App\Interfaces\KemitraanRepositoryInterface;
use App\Models\Universitas;
use App\Models\Perusahaan;
use App\Traits\GeneratesThumbnail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KemitraanService
{
    use GeneratesThumbnail;

    private KemitraanRepositoryInterface $repository;

    public function __construct(KemitraanRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ═══════════════════════════════════════════════════════════
    //  MITRA UNIVERSITAS
    // ═══════════════════════════════════════════════════════════

    /**
     * List all universitas with optional search filter.
     */
    public function getAllUniversitas(?string $search = null): Collection
    {
        return $this->repository->getAllUniversitas($search);
    }

    /**
     * Find a single universitas by ID.
     */
    public function findUniversitas(int $id): ?Universitas
    {
        return $this->repository->findUniversitas($id);
    }

    /**
     * Create a new universitas with optional logo upload.
     */
    public function createUniversitas(
        array $data,
        ?UploadedFile $logoFile = null,
        ?string $logoBase64 = null
    ): Universitas {
        // Handle logo
        $data['logo'] = $this->processLogoUpload($logoFile, $logoBase64, 'kemitraan/universitas');

        return $this->repository->createUniversitas($data);
    }

    /**
     * Update an existing universitas with optional logo replacement.
     */
    public function updateUniversitas(
        int $id,
        array $data,
        ?UploadedFile $logoFile = null,
        ?string $logoBase64 = null,
        bool $removeLogo = false
    ): Universitas {
        $existing = $this->repository->findUniversitas($id);

        if (!$existing) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Universitas dengan ID {$id} tidak ditemukan."
            );
        }

        // Handle logo removal
        if ($removeLogo) {
            $this->deleteLogo($existing->logo);
            $data['logo'] = null;
        }
        // Handle logo replacement
        elseif ($logoFile || $logoBase64) {
            $this->deleteLogo($existing->logo);
            $data['logo'] = $this->processLogoUpload($logoFile, $logoBase64, 'kemitraan/universitas');
        }

        return $this->repository->updateUniversitas($id, $data);
    }

    /**
     * Delete a universitas and its logo file.
     */
    public function deleteUniversitas(int $id): bool
    {
        $universitas = $this->repository->findUniversitas($id);

        if (!$universitas) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Universitas dengan ID {$id} tidak ditemukan."
            );
        }

        $this->deleteLogo($universitas->logo);

        return $this->repository->deleteUniversitas($id);
    }

    // ═══════════════════════════════════════════════════════════
    //  MITRA PERUSAHAAN
    // ═══════════════════════════════════════════════════════════

    /**
     * List all perusahaan with optional search filter.
     */
    public function getAllPerusahaan(?string $search = null): Collection
    {
        return $this->repository->getAllPerusahaan($search);
    }

    /**
     * Find a single perusahaan by ID.
     */
    public function findPerusahaan(int $id): ?Perusahaan
    {
        return $this->repository->findPerusahaan($id);
    }

    /**
     * Create a new perusahaan with optional logo upload.
     */
    public function createPerusahaan(
        array $data,
        ?UploadedFile $logoFile = null,
        ?string $logoBase64 = null
    ): Perusahaan {
        $data['logo'] = $this->processLogoUpload($logoFile, $logoBase64, 'kemitraan/perusahaan');

        return $this->repository->createPerusahaan($data);
    }

    /**
     * Update an existing perusahaan with optional logo replacement.
     */
    public function updatePerusahaan(
        int $id,
        array $data,
        ?UploadedFile $logoFile = null,
        ?string $logoBase64 = null,
        bool $removeLogo = false
    ): Perusahaan {
        $existing = $this->repository->findPerusahaan($id);

        if (!$existing) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Perusahaan dengan ID {$id} tidak ditemukan."
            );
        }

        if ($removeLogo) {
            $this->deleteLogo($existing->logo);
            $data['logo'] = null;
        } elseif ($logoFile || $logoBase64) {
            $this->deleteLogo($existing->logo);
            $data['logo'] = $this->processLogoUpload($logoFile, $logoBase64, 'kemitraan/perusahaan');
        }

        return $this->repository->updatePerusahaan($id, $data);
    }

    /**
     * Delete a perusahaan and its logo file.
     */
    public function deletePerusahaan(int $id): bool
    {
        $perusahaan = $this->repository->findPerusahaan($id);

        if (!$perusahaan) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Perusahaan dengan ID {$id} tidak ditemukan."
            );
        }

        $this->deleteLogo($perusahaan->logo);

        return $this->repository->deletePerusahaan($id);
    }

    // ═══════════════════════════════════════════════════════════
    //  EXPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Get export data for universitas or perusahaan.
     *
     * @param  string  $type  'universitas' or 'perusahaan'
     * @return array{headers: array, rows: array, title: string}
     */
    public function getExportData(string $type): array
    {
        if ($type === 'universitas') {
            $data = $this->repository->getAllUniversitas();

            return [
                'title'   => 'Laporan Data Mitra Universitas',
                'headers' => ['No', 'Nama Kampus/Universitas', 'Alamat Lengkap'],
                'rows'    => $data->values()->map(fn($item, $index) => [
                    $index + 1,
                    $item->nama_universitas,
                    $item->alamat ?? '-',
                ])->toArray(),
            ];
        }

        $data = $this->repository->getAllPerusahaan();

        return [
            'title'   => 'Laporan Data Mitra Perusahaan',
            'headers' => ['No', 'Nama Perusahaan', 'Alamat Lengkap'],
            'rows'    => $data->values()->map(fn($item, $index) => [
                $index + 1,
                $item->nama_perusahaan,
                $item->jalan ?? '-',
            ])->toArray(),
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Process a logo upload (file or base64) and return the stored path.
     */
    private function processLogoUpload(
        ?UploadedFile $file,
        ?string $base64,
        string $directory
    ): ?string {
        if ($file) {
            $result = $this->storeWithThumbnail($file, $directory, 200, 200);
            return $result['path'];
        }

        if ($base64) {
            return $this->storeBase64Image($base64, $directory);
        }

        return null;
    }

    /**
     * Delete a logo and its thumbnail from storage.
     */
    private function deleteLogo(?string $path): void
    {
        if ($path) {
            $this->deleteWithThumbnail($path);
        }
    }

    /**
     * Decode a base64 data URL string and store it as a file.
     *
     * @param  string  $base64String  Format: "data:image/png;base64,iVBOR..."
     * @param  string  $directory     Storage subdirectory.
     * @return string  The stored file path (relative to storage/public).
     */
    private function storeBase64Image(string $base64String, string $directory): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
            $extension = $matches[1];
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $base64Data = substr($base64String, strpos($base64String, ',') + 1);
            $decodedData = base64_decode($base64Data);

            if ($decodedData === false) {
                throw new \InvalidArgumentException('Format base64 tidak valid.');
            }

            $filename = Str::uuid() . '.' . $extension;
            $filePath = $directory . '/' . $filename;

            Storage::disk('public')->makeDirectory($directory);
            Storage::disk('public')->put($filePath, $decodedData);

            return $filePath;
        }

        throw new \InvalidArgumentException('Format data URL gambar tidak valid.');
    }
}
