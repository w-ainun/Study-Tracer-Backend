<?php

namespace App\Repositories\Alumni;

use App\Interfaces\Alumni\ProfileRepositoryInterface;
use App\Models\Alumni;
use App\Models\AlumniSkill;
use App\Models\AlumniSocialMedia;
use App\Models\RiwayatStatus;

class ProfileRepository implements ProfileRepositoryInterface
{
    /**
     * All relations needed for the full profile view.
     */
    private array $profileRelations = [
        'jurusan',
        'user',
        'skills',
        'socialMedia',
        'riwayatStatus' => null, // will be overridden with closure
        'riwayatStatus.status',
        'riwayatStatus.pekerjaan.perusahaan.kota.provinsi',
        'riwayatStatus.kuliah.universitas.kota.provinsi',
        'riwayatStatus.kuliah.jurusanKuliah',
        'riwayatStatus.wirausaha.kota.provinsi',
        'riwayatStatus.wirausaha.bidangUsaha',
    ];

    /**
     * Get full alumni profile by user ID.
     */
    public function getProfileByUserId(int $userId)
    {
        return Alumni::with([
            'jurusan',
            'user',
            'skills',
            'socialMedia',
            'riwayatStatus' => fn($q) => $q->orderByDesc('id_riwayat'),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan.kota.provinsi',
            'riwayatStatus.kuliah.universitas.kota.provinsi',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha.kota.provinsi',
            'riwayatStatus.wirausaha.bidangUsaha',
            'riwayatStatus.deskripsiKarier', // ✓ Eager-loaded
            'portofolio', // ✓ Eager-loaded
            'pendingProfileUpdates' => fn($q) => $q->where('status', 'pending')->orderByDesc('created_at'),
        ])
            ->where('id_users', $userId)
            ->first();
    }

    /**
     * Update alumni basic profile fields.
     */
    public function updateProfile(int $alumniId, array $data)
    {
        $alumni = Alumni::findOrFail($alumniId);
        $alumni->update($data);
        return $alumni->fresh();
    }

    /**
     * Replace all skills for the alumni.
     */
    public function syncSkills(int $alumniId, array $skillIds)
    {
        AlumniSkill::where('id_alumni', $alumniId)->delete();

        foreach ($skillIds as $skillId) {
            AlumniSkill::create([
                'id_alumni' => $alumniId,
                'id_skills' => $skillId,
            ]);
        }
    }

    /**
     * Replace all social media links for the alumni.
     */
    public function syncSocialMedia(int $alumniId, array $socialMediaData)
    {
        AlumniSocialMedia::where('id_alumni', $alumniId)->delete();

        foreach ($socialMediaData as $item) {
            AlumniSocialMedia::create([
                'id_alumni' => $alumniId,
                'id_sosmed' => $item['id_sosmed'],
                'url' => $item['url'],
                'create_at' => now(),
            ]);
        }
    }

    /**
     * Create a new riwayat status record.
     */
    public function createRiwayatStatus(int $alumniId, array $data)
    {
        return RiwayatStatus::create(array_merge($data, ['id_alumni' => $alumniId]));
    }

    /**
     * Get alumni with all relations freshly loaded.
     */
    public function getAlumniWithRelations(int $alumniId)
    {
        return Alumni::with([
            'jurusan',
            'user',
            'skills',
            'socialMedia',
            'riwayatStatus' => fn($q) => $q->orderByDesc('id_riwayat'),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan.kota.provinsi',
            'riwayatStatus.kuliah.universitas.kota.provinsi',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha.kota.provinsi',
            'riwayatStatus.wirausaha.bidangUsaha',
            'riwayatStatus.deskripsiKarier',
            'portofolio',
            'pendingProfileUpdates' => fn($q) => $q->where('status', 'pending')->orderByDesc('created_at'),
        ])->findOrFail($alumniId);
    }

    /**
     * Set alumni status_create to 'pending' so admin must re-approve.
     */
    public function setStatusPending(int $alumniId): void
    {
        Alumni::where('id_alumni', $alumniId)->update(['status_create' => 'pending']);
    }

    /**
     * Get the latest riwayat status for an alumni.
     */
    public function getLatestRiwayat(int $alumniId)
    {
        return RiwayatStatus::with([
            'status',
            'pekerjaan.perusahaan.kota.provinsi',
            'kuliah.universitas.kota.provinsi',
            'kuliah.jurusanKuliah',
            'wirausaha.kota.provinsi',
            'wirausaha.bidangUsaha',
        ])
            ->where('id_alumni', $alumniId)
            ->orderByDesc('id_riwayat')
            ->first();
    }
}
