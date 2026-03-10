<?php

namespace App\Services\Alumni;

use App\Models\DeskripsiKarier;
use App\Models\RiwayatStatus;
use Illuminate\Support\Facades\DB;

class DeskripsiKarierService
{
    /**
     * Get all deskripsi karier for an alumni
     */
    public function getByAlumniId(int $alumniId): array
    {
        return DeskripsiKarier::whereHas('riwayatStatus', function ($query) use ($alumniId) {
            $query->where('id_alumni', $alumniId)
                  ->where('approval_status', 'approved');
        })
        ->with(['riwayatStatus.status', 'riwayatStatus.pekerjaan.perusahaan', 'riwayatStatus.kuliah.universitas', 'riwayatStatus.wirausaha'])
        ->orderBy('created_at', 'desc')
        ->get()
        ->toArray();
    }

    /**
     * Create deskripsi karier for a riwayat_status
     */
    public function create(int $alumniId, array $data): DeskripsiKarier
    {
        // Verify riwayat belongs to alumni
        $riwayat = RiwayatStatus::where('id_riwayat', $data['id_riwayat'])
            ->where('id_alumni', $alumniId)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        return DB::transaction(function () use ($data) {
            return DeskripsiKarier::create([
                'id_riwayat' => $data['id_riwayat'],
                'deskripsi' => $data['deskripsi'],
            ]);
        });
    }

    /**
     * Update deskripsi karier
     * Delete old data and create new one
     */
    public function update(int $alumniId, int $id, array $data): DeskripsiKarier
    {
        return DB::transaction(function () use ($alumniId, $id, $data) {
            // Find and verify ownership of old deskripsi
            $oldDeskripsi = DeskripsiKarier::whereHas('riwayatStatus', function ($query) use ($alumniId) {
                $query->where('id_alumni', $alumniId);
            })->findOrFail($id);

            // Verify new riwayat belongs to alumni
            $riwayat = RiwayatStatus::where('id_riwayat', $data['id_riwayat'])
                ->where('id_alumni', $alumniId)
                ->where('approval_status', 'approved')
                ->firstOrFail();

            // Delete old data
            $oldDeskripsi->delete();

            // Create new data
            $newDeskripsi = DeskripsiKarier::create([
                'id_riwayat' => $data['id_riwayat'],
                'deskripsi' => $data['deskripsi'],
            ]);

            return $newDeskripsi->load(['riwayatStatus.status', 'riwayatStatus.pekerjaan.perusahaan', 'riwayatStatus.kuliah.universitas', 'riwayatStatus.wirausaha']);
        });
    }

    /**
     * Delete deskripsi karier
     */
    public function delete(int $alumniId, int $id): bool
    {
        $deskripsi = DeskripsiKarier::whereHas('riwayatStatus', function ($query) use ($alumniId) {
            $query->where('id_alumni', $alumniId);
        })->findOrFail($id);

        return $deskripsi->delete();
    }
}
