<?php

namespace App\Services\Alumni;

use App\Models\DeskripsiKarier;
use App\Models\PendingProfileUpdate;
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
     * Create deskripsi karier — saves as pending for admin approval.
     */
    public function create(int $alumniId, array $data): PendingProfileUpdate
    {
        // Verify riwayat belongs to alumni and is approved
        $riwayat = RiwayatStatus::where('id_riwayat', $data['id_riwayat'])
            ->where('id_alumni', $alumniId)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        // Check for existing pending deskripsi_karier create for same riwayat
        $existingPending = PendingProfileUpdate::where('id_alumni', $alumniId)
            ->where('section', 'deskripsi_karier')
            ->where('status', 'pending')
            ->whereJsonContains('new_data->id_riwayat', $data['id_riwayat'])
            ->first();

        if ($existingPending) {
            throw new \Exception('Anda sudah memiliki pembaruan deskripsi karier yang sedang menunggu persetujuan admin.');
        }

        return PendingProfileUpdate::create([
            'id_alumni' => $alumniId,
            'section' => 'deskripsi_karier',
            'action' => 'create',
            'old_data' => null,
            'new_data' => [
                'id_riwayat' => $data['id_riwayat'],
                'deskripsi' => $data['deskripsi'],
            ],
        ]);
    }

    /**
     * Update deskripsi karier — saves as pending for admin approval.
     */
    public function update(int $alumniId, int $id, array $data): PendingProfileUpdate
    {
        // Find and verify ownership
        $oldDeskripsi = DeskripsiKarier::whereHas('riwayatStatus', function ($query) use ($alumniId) {
            $query->where('id_alumni', $alumniId);
        })->findOrFail($id);

        // Verify new riwayat belongs to alumni
        RiwayatStatus::where('id_riwayat', $data['id_riwayat'])
            ->where('id_alumni', $alumniId)
            ->where('approval_status', 'approved')
            ->firstOrFail();

        // Check for existing pending update for same deskripsi
        $existingPending = PendingProfileUpdate::where('id_alumni', $alumniId)
            ->where('section', 'deskripsi_karier')
            ->where('action', 'update')
            ->where('related_id', $id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            // Update the existing pending instead of creating new one
            $existingPending->update([
                'new_data' => [
                    'id_riwayat' => $data['id_riwayat'],
                    'deskripsi' => $data['deskripsi'],
                ],
            ]);
            return $existingPending->fresh();
        }

        return PendingProfileUpdate::create([
            'id_alumni' => $alumniId,
            'section' => 'deskripsi_karier',
            'action' => 'update',
            'related_id' => $id,
            'old_data' => [
                'id_riwayat' => $oldDeskripsi->id_riwayat,
                'deskripsi' => $oldDeskripsi->deskripsi,
            ],
            'new_data' => [
                'id_riwayat' => $data['id_riwayat'],
                'deskripsi' => $data['deskripsi'],
            ],
        ]);
    }

    /**
     * Delete deskripsi karier — saves as pending for admin approval.
     */
    public function delete(int $alumniId, int $id): PendingProfileUpdate
    {
        $deskripsi = DeskripsiKarier::whereHas('riwayatStatus', function ($query) use ($alumniId) {
            $query->where('id_alumni', $alumniId);
        })->findOrFail($id);

        // Check for existing pending delete
        $existingPending = PendingProfileUpdate::where('id_alumni', $alumniId)
            ->where('section', 'deskripsi_karier')
            ->where('action', 'delete')
            ->where('related_id', $id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            throw new \Exception('Penghapusan deskripsi karier ini sudah menunggu persetujuan admin.');
        }

        return PendingProfileUpdate::create([
            'id_alumni' => $alumniId,
            'section' => 'deskripsi_karier',
            'action' => 'delete',
            'related_id' => $id,
            'old_data' => [
                'id_riwayat' => $deskripsi->id_riwayat,
                'deskripsi' => $deskripsi->deskripsi,
            ],
            'new_data' => null,
        ]);
    }

    /**
     * Update an existing pending deskripsi karier (re-edit before admin approval).
     */
    public function updatePending(int $alumniId, int $pendingId, array $data): PendingProfileUpdate
    {
        $pending = PendingProfileUpdate::where('id', $pendingId)
            ->where('id_alumni', $alumniId)
            ->where('section', 'deskripsi_karier')
            ->where('status', 'pending')
            ->firstOrFail();

        $pending->update([
            'new_data' => [
                'id_riwayat' => $data['id_riwayat'] ?? $pending->new_data['id_riwayat'],
                'deskripsi'  => $data['deskripsi'],
            ],
        ]);

        return $pending->fresh();
    }

    /**
     * Cancel (delete) a pending deskripsi karier update.
     */
    public function cancelPending(int $alumniId, int $pendingId): void
    {
        $pending = PendingProfileUpdate::where('id', $pendingId)
            ->where('id_alumni', $alumniId)
            ->where('section', 'deskripsi_karier')
            ->where('status', 'pending')
            ->firstOrFail();

        $pending->delete();
    }
}
