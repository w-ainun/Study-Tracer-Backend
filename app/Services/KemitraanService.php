<?php

namespace App\Services;

use App\Interfaces\KemitraanRepositoryInterface;
use App\Models\Kemitraan;
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
    //  CRUD
    // ═══════════════════════════════════════════════════════════

    /**
     * List all kemitraan by tipe with optional search.
     */
    public function getAll(string $tipe, ?string $search = null): Collection
    {
        return $this->repository->getAll($tipe, $search);
    }

    /**
     * Find a single kemitraan by ID.
     */
    public function find(int $id): ?Kemitraan
    {
        return $this->repository->find($id);
    }

    /**
     * Create a new kemitraan record with optional logo.
     */
    public function create(
        array $data,
        ?UploadedFile $logoFile = null,
        ?string $logoBase64 = null
    ): Kemitraan {
        $data['logo'] = $this->processLogoUpload($logoFile, $logoBase64, 'kemitraan/' . $data['tipe']);

        return $this->repository->create($data);
    }

    /**
     * Update an existing kemitraan record.
     */
    public function update(
        int $id,
        array $data,
        ?UploadedFile $logoFile = null,
        ?string $logoBase64 = null,
        bool $removeLogo = false
    ): Kemitraan {
        $existing = $this->repository->find($id);

        if (!$existing) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Kemitraan dengan ID {$id} tidak ditemukan."
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
            $data['logo'] = $this->processLogoUpload(
                $logoFile,
                $logoBase64,
                'kemitraan/' . $existing->tipe
            );
        }

        return $this->repository->update($id, $data);
    }

    /**
     * Delete a kemitraan record and its logo.
     */
    public function delete(int $id): bool
    {
        $kemitraan = $this->repository->find($id);

        if (!$kemitraan) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException(
                "Kemitraan dengan ID {$id} tidak ditemukan."
            );
        }

        $this->deleteLogo($kemitraan->logo);

        return $this->repository->delete($id);
    }

    // ═══════════════════════════════════════════════════════════
    //  EXPORT
    // ═══════════════════════════════════════════════════════════

    /**
     * Get export data for a specific tipe.
     *
     * @param  string  $tipe  'universitas' or 'perusahaan'
     * @return array{headers: array, rows: array, title: string}
     */
    public function getExportData(string $tipe): array
    {
        $data = $this->repository->getAll($tipe);

        $title = $tipe === 'universitas'
            ? 'Laporan Data Mitra Universitas'
            : 'Laporan Data Mitra Perusahaan';

        $label = $tipe === 'universitas'
            ? 'Nama Kampus/Universitas'
            : 'Nama Perusahaan';

        return [
            'title'   => $title,
            'headers' => ['No', $label, 'Alamat Lengkap'],
            'rows'    => $data->values()->map(fn($item, $index) => [
                $index + 1,
                $item->nama,
                $item->alamat ?? '-',
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
