<?php

namespace App\Repositories;

use App\Interfaces\AlumniRepositoryInterface;
use App\Models\Alumni;
use App\Models\AlumniSkill;
use App\Models\AlumniSocialMedia;
use App\Models\RiwayatStatus;

class AlumniRepository implements AlumniRepositoryInterface
{
    public function getAlumniByUserId(int $userId)
    {
        return Alumni::with(['jurusan', 'skills', 'socialMedia', 'riwayatStatus.status', 'riwayatStatus.pekerjaan.perusahaan', 'riwayatStatus.universitas.jurusanKuliah', 'riwayatStatus.wirausaha.bidangUsaha'])
            ->where('id_users', $userId)
            ->first();
    }

    public function updateProfile(int $alumniId, array $data)
    {
        $alumni = Alumni::findOrFail($alumniId);
        $alumni->update($data);
        return $alumni->fresh();
    }

    public function syncSkills(int $alumniId, array $skillIds)
    {
        // Remove old skills
        AlumniSkill::where('id_alumni', $alumniId)->delete();

        // Add new skills
        foreach ($skillIds as $skillId) {
            AlumniSkill::create([
                'id_alumni' => $alumniId,
                'id_skills' => $skillId,
            ]);
        }
    }

    public function syncSocialMedia(int $alumniId, array $socialMediaData)
    {
        // Remove old social media
        AlumniSocialMedia::where('id_alumni', $alumniId)->delete();

        // Add new social media
        foreach ($socialMediaData as $item) {
            AlumniSocialMedia::create([
                'id_alumni' => $alumniId,
                'id_sosmed' => $item['id_sosmed'],
                'url' => $item['url'],
                'create_at' => now(),
            ]);
        }
    }

    public function createRiwayatStatus(int $alumniId, array $data)
    {
        return RiwayatStatus::create(array_merge($data, ['id_alumni' => $alumniId]));
    }

    public function getAlumniWithRelations(int $alumniId, array $relations = [])
    {
        $defaultRelations = ['jurusan', 'skills', 'socialMedia', 'user', 'riwayatStatus.status'];
        $relations = array_merge($defaultRelations, $relations);

        return Alumni::with($relations)->findOrFail($alumniId);
    }
}
