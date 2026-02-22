<?php

namespace App\Interfaces;

interface AlumniRepositoryInterface
{
    public function getAlumniByUserId(int $userId);
    public function updateProfile(int $alumniId, array $data);
    public function syncSkills(int $alumniId, array $skillIds);
    public function syncSocialMedia(int $alumniId, array $socialMediaData);
    public function createRiwayatStatus(int $alumniId, array $data);
    public function getAlumniWithRelations(int $alumniId, array $relations = []);
}
