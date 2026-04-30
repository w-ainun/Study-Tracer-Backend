<?php

namespace App\Services;

use App\Interfaces\KelulusanRepositoryInterface;
use App\Models\CalonLulusan;
use App\Models\Jurusan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class KelulusanService
{
    private KelulusanRepositoryInterface $kelulusanRepository;

    public function __construct(KelulusanRepositoryInterface $kelulusanRepository)
    {
        $this->kelulusanRepository = $kelulusanRepository;
    }

    // ═══════════════════════════════════════════════
    //  CALON LULUSAN (STAGING)
    // ═══════════════════════════════════════════════

    /**
     * Get all calon lulusan with filters.
     */
    public function getCalonLulusan(array $filters = [], int $perPage = 50)
    {
        return $this->kelulusanRepository->getCalonLulusan($filters, $perPage);
    }

    /**
     * Create a single calon lulusan manually.
     */
    public function createCalonLulusan(array $data)
    {
        return $this->kelulusanRepository->createCalonLulusan($data);
    }

    /**
     * Import calon lulusan from Excel/CSV file.
     *
     * Expected columns: NISN, Nama, Jurusan
     * Returns summary of import results.
     */
    public function importFromExcel(UploadedFile $file, int $adminUserId): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
            throw ValidationException::withMessages([
                'file' => ['Format file harus .csv, .xlsx, atau .xls'],
            ]);
        }

        // Parse the file based on extension
        $rows = $this->parseFile($file, $extension);

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'file' => ['File tidak berisi data atau format kolom salah. Pastikan ada kolom: NISN, Nama, Jurusan'],
            ]);
        }

        // Pre-load jurusan name→ID mapping for fast lookup
        $jurusanMap = Jurusan::pluck('id_jurusan', 'nama_jurusan')
            ->mapWithKeys(fn ($id, $name) => [strtolower($name) => $id])
            ->toArray();

        $batchId = Str::uuid()->toString();
        $prepared = [];
        $errors = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // +2 = header row + 0-indexed

            $nisn = trim($row['nisn'] ?? '');
            $nama = trim($row['nama'] ?? '');
            $jurusanName = trim($row['jurusan'] ?? '');

            // Validate required fields
            if (empty($nisn) || empty($nama) || empty($jurusanName)) {
                $errors[] = "Baris {$rowNum}: Data tidak lengkap (NISN, Nama, atau Jurusan kosong)";
                $skipped++;
                continue;
            }

            // Resolve jurusan ID
            $jurusanId = $jurusanMap[strtolower($jurusanName)] ?? null;
            if (!$jurusanId) {
                $errors[] = "Baris {$rowNum}: Jurusan '{$jurusanName}' tidak ditemukan di database";
                $skipped++;
                continue;
            }

            $prepared[] = [
                'nisn'        => $nisn,
                'nama'        => $nama,
                'id_jurusan'  => $jurusanId,
                'imported_by' => $adminUserId,
                'batch_id'    => $batchId,
            ];
        }

        $inserted = 0;
        if (!empty($prepared)) {
            $inserted = $this->kelulusanRepository->bulkCreateCalonLulusan($prepared);
        }

        return [
            'batch_id' => $batchId,
            'total_rows' => count($rows),
            'inserted' => $inserted,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 20), // Limit error messages
        ];
    }

    /**
     * Delete a single calon lulusan.
     */
    public function deleteCalonLulusan(int $id): bool
    {
        return $this->kelulusanRepository->deleteCalonLulusan($id);
    }

    /**
     * Clear all calon lulusan (empty staging table).
     */
    public function clearCalonLulusan(): int
    {
        return $this->kelulusanRepository->clearCalonLulusan();
    }

    // ═══════════════════════════════════════════════
    //  SIMPAN KELULUSAN (CONFIRM GRADUATION)
    // ═══════════════════════════════════════════════

    /**
     * Confirm graduation: move all calon_lulusan → riwayat_kelulusan.
     *
     * This is the core business operation:
     * 1. Read all staging records
     * 2. Insert into riwayat_kelulusan with tahun_lulus
     * 3. Clear staging table
     * 4. All within a DB transaction for atomicity
     */
    public function simpanKelulusan(int $adminUserId, ?int $tahunLulus = null): array
    {
        $tahunLulus = $tahunLulus ?? (int) date('Y');

        // Get all calon lulusan
        $calonList = CalonLulusan::all();

        if ($calonList->isEmpty()) {
            throw ValidationException::withMessages([
                'calon' => ['Tidak ada data calon lulusan untuk diproses'],
            ]);
        }

        $batchId = Str::uuid()->toString();

        return DB::transaction(function () use ($calonList, $tahunLulus, $adminUserId, $batchId) {
            // Prepare riwayat kelulusan data
            $riwayatData = $calonList->map(function ($calon) use ($tahunLulus, $adminUserId, $batchId) {
                return [
                    'nisn'         => $calon->nisn,
                    'nama'         => $calon->nama,
                    'id_jurusan'   => $calon->id_jurusan,
                    'tahun_lulus'  => $tahunLulus,
                    'confirmed_by' => $adminUserId,
                    'batch_id'     => $batchId,
                ];
            })->toArray();

            // Insert into riwayat_kelulusan
            $inserted = $this->kelulusanRepository->bulkCreateRiwayatKelulusan($riwayatData);

            // Clear staging table
            $this->kelulusanRepository->clearCalonLulusan();

            Log::info('Kelulusan confirmed', [
                'admin_id'    => $adminUserId,
                'batch_id'    => $batchId,
                'tahun_lulus' => $tahunLulus,
                'total'       => $inserted,
            ]);

            return [
                'batch_id'    => $batchId,
                'tahun_lulus' => $tahunLulus,
                'total'       => $inserted,
            ];
        });
    }

    // ═══════════════════════════════════════════════
    //  RIWAYAT KELULUSAN (READ & EXPORT)
    // ═══════════════════════════════════════════════

    /**
     * Get riwayat kelulusan with filters.
     */
    public function getRiwayatKelulusan(array $filters = [], int $perPage = 15)
    {
        return $this->kelulusanRepository->getRiwayatKelulusan($filters, $perPage);
    }

    /**
     * Get distinct tahun_lulus for filter dropdown.
     */
    public function getDistinctTahunLulus(): array
    {
        return $this->kelulusanRepository->getDistinctTahunLulus();
    }

    /**
     * Get kelulusan statistics.
     */
    public function getStats(): array
    {
        return $this->kelulusanRepository->getStats();
    }

    /**
     * Stream riwayat kelulusan for export.
     */
    public function streamRiwayatKelulusan(array $filters, callable $callback)
    {
        $this->kelulusanRepository->streamRiwayatKelulusan($filters, $callback);
    }

    // ═══════════════════════════════════════════════
    //  FILE PARSING (CSV / XLSX)
    // ═══════════════════════════════════════════════

    /**
     * Parse uploaded file into array of rows.
     * Each row: ['nisn' => ..., 'nama' => ..., 'jurusan' => ...]
     */
    private function parseFile(UploadedFile $file, string $extension): array
    {
        if ($extension === 'csv') {
            return $this->parseCsv($file);
        }

        // For .xlsx and .xls, use PhpSpreadsheet if available, else CSV fallback
        if (class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            return $this->parseSpreadsheet($file);
        }

        // If PhpSpreadsheet not installed, try CSV parsing
        Log::warning('PhpSpreadsheet not installed, attempting CSV fallback for Excel file');
        return $this->parseCsv($file);
    }

    /**
     * Parse CSV file.
     */
    private function parseCsv(UploadedFile $file): array
    {
        $rows = [];
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return [];
        }

        // Read header row
        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);
            return [];
        }

        // Normalize header names (case-insensitive, trim)
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        // Map header positions
        $nisnIdx = $this->findColumnIndex($header, ['nisn']);
        $namaIdx = $this->findColumnIndex($header, ['nama', 'nama_siswa', 'nama siswa', 'name']);
        $jurusanIdx = $this->findColumnIndex($header, ['jurusan', 'program_studi', 'prodi']);

        if ($nisnIdx === null || $namaIdx === null || $jurusanIdx === null) {
            fclose($handle);
            return [];
        }

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) <= max($nisnIdx, $namaIdx, $jurusanIdx)) {
                continue;
            }

            $rows[] = [
                'nisn'    => $row[$nisnIdx] ?? '',
                'nama'    => $row[$namaIdx] ?? '',
                'jurusan' => $row[$jurusanIdx] ?? '',
            ];
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Parse Excel file using PhpSpreadsheet.
     */
    private function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = [];

        $headerRow = [];
        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headerRow[] = strtolower(trim($cell->getValue() ?? ''));
            }
        }

        $nisnIdx = $this->findColumnIndex($headerRow, ['nisn']);
        $namaIdx = $this->findColumnIndex($headerRow, ['nama', 'nama_siswa', 'nama siswa', 'name']);
        $jurusanIdx = $this->findColumnIndex($headerRow, ['jurusan', 'program_studi', 'prodi']);

        if ($nisnIdx === null || $namaIdx === null || $jurusanIdx === null) {
            return [];
        }

        foreach ($worksheet->getRowIterator(2) as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = trim($cell->getValue() ?? '');
            }

            if (count($cells) <= max($nisnIdx, $namaIdx, $jurusanIdx)) {
                continue;
            }

            $nisn = $cells[$nisnIdx] ?? '';
            $nama = $cells[$namaIdx] ?? '';
            $jurusan = $cells[$jurusanIdx] ?? '';

            if (empty($nisn) && empty($nama)) {
                continue; // Skip fully empty rows
            }

            $rows[] = [
                'nisn'    => $nisn,
                'nama'    => $nama,
                'jurusan' => $jurusan,
            ];
        }

        return $rows;
    }

    /**
     * Find column index by matching against multiple possible header names.
     */
    private function findColumnIndex(array $header, array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            $idx = array_search($name, $header);
            if ($idx !== false) {
                return $idx;
            }
        }
        return null;
    }
}
