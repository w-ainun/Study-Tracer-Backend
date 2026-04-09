<?php

namespace App\Services;

use App\Events\AccountStatusChanged;
use App\Events\AccessLockChanged;
use App\Events\DashboardStatsUpdated;
use App\Interfaces\AdminRepositoryInterface;
use App\Jobs\SendNotificationJob;
use App\Models\Alumni;
use App\Models\AlumniSkill;
use App\Models\AlumniSocialMedia;
use App\Models\DeskripsiKarier;
use App\Models\PendingProfileUpdate;
use App\Models\Portofolio;
use App\Traits\GeneratesThumbnail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminService
{
    use GeneratesThumbnail;
    private AdminRepositoryInterface $adminRepository;
    private NotificationService $notificationService;

    public function __construct(
        AdminRepositoryInterface $adminRepository,
        NotificationService $notificationService
    ) {
        $this->adminRepository = $adminRepository;
        $this->notificationService = $notificationService;
    }

    public function getDashboardStats(): array
    {
        return $this->adminRepository->getDashboardStats();
    }

    public function getUserManagementStats(): array
    {
        return $this->adminRepository->getUserManagementStats();
    }

    public function getPendingAlumni(int $perPage = 15)
    {
        return $this->adminRepository->getPendingAlumni($perPage);
    }

    public function approveAlumni(int $alumniId)
    {
        $alumni = $this->adminRepository->approveAlumni($alumniId);
        
        // Dispatch notifikasi ke queue (non-blocking)
        if ($alumni && $alumni->id_users) {
            SendNotificationJob::dispatch($alumni->id_users, 'notifyAccountVerified');
            
            // Clear cache setelah approve alumni
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:can_access_all");
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:kuesioner_completed");

            // Broadcast account status change & access lock update
            broadcast(new AccountStatusChanged($alumni->id_users, 'verified'))->toOthers();
            broadcast(new AccessLockChanged($alumni->id_users, true))->toOthers();
        }
        
        return $alumni;
    }

    public function rejectAlumni(int $alumniId)
    {
        $alumni = $this->adminRepository->rejectAlumni($alumniId);
        
        // Dispatch notifikasi ke queue (non-blocking)
        if ($alumni && $alumni->id_users) {
            SendNotificationJob::dispatch($alumni->id_users, 'notifyAccountRejected');
            
            // Clear cache setelah reject alumni
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:can_access_all");
            \Illuminate\Support\Facades\Cache::forget("user:{$alumni->id_users}:kuesioner_completed");

            // Broadcast account rejection
            broadcast(new AccountStatusChanged($alumni->id_users, 'rejected'))->toOthers();
        }
        
        return $alumni;
    }

    public function banAlumni(int $alumniId)
    {
        $alumni = $this->adminRepository->banAlumni($alumniId);
        
        // Dispatch notifikasi ke queue (non-blocking)
        if ($alumni && $alumni->id_users) {
            SendNotificationJob::dispatch($alumni->id_users, 'notifyAccountBanned');

            // Broadcast ban status — frontend should force logout
            broadcast(new AccountStatusChanged($alumni->id_users, 'banned'))->toOthers();
        }
        
        return $alumni;
    }

    public function getAllAlumni(array $filters = [], int $perPage = 15)
    {
        return $this->adminRepository->getAllAlumni($filters, $perPage);
    }

    public function getAlumniDetail(int $alumniId)
    {
        return $this->adminRepository->getAlumniDetail($alumniId);
    }

    public function getFeaturedAlumni(int $limit = 8)
    {
        return $this->adminRepository->getFeaturedAlumni($limit);
    }

    public function syncFeaturedAlumni(array $alumniIds, int $adminUserId): array
    {
        return $this->adminRepository->syncFeaturedAlumni($alumniIds, $adminUserId);
    }

    public function setFeaturedAlumni(int $alumniId, bool $isSelected, int $adminUserId): bool
    {
        return $this->adminRepository->setFeaturedAlumni($alumniId, $isSelected, $adminUserId);
    }

    public function deleteUser(int $userId)
    {
        // Get user data before deleting to clean up files
        $user = \App\Models\User::with('alumni')
            ->where('id_users', $userId)
            ->orWhereHas('alumni', function ($query) use ($userId) {
                $query->where('id_alumni', $userId);
            })
            ->first();
        
        // Delete alumni foto and thumbnail if exists
        if ($user && $user->alumni && $user->alumni->foto) {
            $this->deleteWithThumbnail($user->alumni->foto);
        }
        
        return $this->adminRepository->deleteUser($userId);
    }

    public function getLowonganStats(): array
    {
        return $this->adminRepository->getLowonganStats();
    }

    public function getTopCompanies(int $limit = 5): array
    {
        return $this->adminRepository->getTopCompanies($limit);
    }

    public function getGeographicDistribution(): array
    {
        return $this->adminRepository->getGeographicDistribution();
    }

    // ── Pending Career Status ────────────────────────

    public function getPendingCareerUpdates()
    {
        return $this->adminRepository->getPendingCareerUpdates();
    }

    public function approveCareerUpdate(int $riwayatId)
    {
        $riwayat = $this->adminRepository->approveCareerUpdate($riwayatId);
        
        // Dispatch notifikasi ke queue (non-blocking)
        if ($riwayat && $riwayat->alumni && $riwayat->alumni->id_users) {
            $statusName = $riwayat->status->nama_status ?? 'Status Karir';
            SendNotificationJob::dispatch(
                $riwayat->alumni->id_users,
                'notifyCareerStatusApproved',
                [$riwayatId, $statusName]
            );
        }
        
        return $riwayat;
    }

    public function rejectCareerUpdate(int $riwayatId)
    {
        // Get data sebelum delete (untuk notifikasi)
        $riwayat = \App\Models\RiwayatStatus::with(['alumni', 'status'])->findOrFail($riwayatId);
        $userId = $riwayat->alumni->id_users ?? null;
        $statusName = $riwayat->status->nama_status ?? 'Status Karir';
        
        $result = $this->adminRepository->rejectCareerUpdate($riwayatId);
        
        // Dispatch notifikasi ke queue (non-blocking)
        if ($userId) {
            SendNotificationJob::dispatch(
                $userId,
                'notifyCareerStatusRejected',
                [$riwayatId, $statusName]
            );
        }
        
        return $result;
    }

    // ── Pending Profile Updates ──────────────────────

    public function getPendingProfileUpdates()
    {
        return $this->adminRepository->getPendingProfileUpdates();
    }

    public function approveProfileUpdate(int $id, int $adminUserId)
    {
        $pending = $this->adminRepository->findPendingProfileUpdate($id);

        if ($pending->status !== 'pending') {
            throw new \Exception('Permintaan ini sudah diproses.');
        }

        return DB::transaction(function () use ($pending, $adminUserId) {
            $this->applyProfileUpdate($pending);

            $pending->update([
                'status' => 'approved',
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
            ]);

            // Dispatch notifikasi ke queue (non-blocking)
            if ($pending->alumni && $pending->alumni->id_users) {
                $sectionLabel = $this->getSectionLabel($pending->section);
                SendNotificationJob::dispatch(
                    $pending->alumni->id_users,
                    'notifyProfileUpdateApproved',
                    [$pending->id, $sectionLabel]
                );
            }

            return $pending;
        });
    }

    public function rejectProfileUpdate(int $id, int $adminUserId, ?string $reason = null)
    {
        $pending = $this->adminRepository->findPendingProfileUpdate($id);

        if ($pending->status !== 'pending') {
            throw new \Exception('Permintaan ini sudah diproses.');
        }

        return DB::transaction(function () use ($pending, $adminUserId, $reason) {
            // Clean up temp files if any
            if ($pending->foto_path) {
                $this->deleteWithThumbnail($pending->foto_path);
            }
            if ($pending->gambar_path) {
                $this->deleteWithThumbnail($pending->gambar_path);
            }

            $pending->update([
                'status' => 'rejected',
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
            ]);

            // Dispatch notifikasi ke queue (non-blocking)
            if ($pending->alumni && $pending->alumni->id_users) {
                $sectionLabel = $this->getSectionLabel($pending->section);
                SendNotificationJob::dispatch(
                    $pending->alumni->id_users,
                    'notifyProfileUpdateRejected',
                    [$pending->id, $sectionLabel, $reason]
                );
            }

            return $pending;
        });
    }

    /**
     * Apply the pending profile update to the actual data.
     */
    private function applyProfileUpdate(PendingProfileUpdate $pending): void
    {
        $alumni = Alumni::findOrFail($pending->id_alumni);
        $newData = $pending->new_data;

        switch ($pending->section) {
            case 'personal_info':
                $this->applyPersonalInfo($alumni, $newData, $pending);
                break;

            case 'skills':
                $this->applySkills($alumni, $newData);
                break;

            case 'social_media':
                $this->applySocialMedia($alumni, $newData);
                break;

            case 'deskripsi_karier':
                $this->applyDeskripsiKarier($alumni, $pending);
                break;

            case 'portofolio':
                $this->applyPortofolio($alumni, $pending);
                break;
        }
    }

    private function applyPersonalInfo(Alumni $alumni, ?array $newData, PendingProfileUpdate $pending): void
    {
        if (!$newData) return;

        // If new foto, move from pending to permanent location and delete old
        if ($pending->foto_path) {
            if ($alumni->foto) {
                $this->deleteWithThumbnail($alumni->foto);
            }
            // Move pending foto to permanent location
            $newPath = str_replace('alumni/foto/pending', 'alumni/foto', $pending->foto_path);
            if (Storage::disk('public')->exists($pending->foto_path)) {
                Storage::disk('public')->move($pending->foto_path, $newPath);
                // Move thumbnail too
                $oldThumb = GeneratesThumbnail::thumbnailPath($pending->foto_path);
                $newThumb = GeneratesThumbnail::thumbnailPath($newPath);
                if ($oldThumb && Storage::disk('public')->exists($oldThumb)) {
                    Storage::disk('public')->move($oldThumb, $newThumb);
                }
            }
            $newData['foto'] = $newPath;
        } else {
            unset($newData['foto']);
        }

        $alumni->update($newData);
    }

    private function applySkills(Alumni $alumni, ?array $newData): void
    {
        if (!$newData || !isset($newData['skill_ids'])) return;

        AlumniSkill::where('id_alumni', $alumni->id_alumni)->delete();
        foreach ($newData['skill_ids'] as $skillId) {
            AlumniSkill::create([
                'id_alumni' => $alumni->id_alumni,
                'id_skills' => $skillId,
            ]);
        }
    }

    private function applySocialMedia(Alumni $alumni, ?array $newData): void
    {
        if (!$newData || !isset($newData['social_media'])) return;

        AlumniSocialMedia::where('id_alumni', $alumni->id_alumni)->delete();
        foreach ($newData['social_media'] as $item) {
            AlumniSocialMedia::create([
                'id_alumni' => $alumni->id_alumni,
                'id_sosmed' => $item['id_sosmed'],
                'url' => $item['url'],
                'create_at' => now(),
            ]);
        }
    }

    private function applyDeskripsiKarier(Alumni $alumni, PendingProfileUpdate $pending): void
    {
        $newData = $pending->new_data;

        switch ($pending->action) {
            case 'create':
                DeskripsiKarier::create([
                    'id_riwayat' => $newData['id_riwayat'],
                    'deskripsi' => $newData['deskripsi'],
                ]);
                break;

            case 'update':
                $deskripsi = DeskripsiKarier::find($pending->related_id);
                if ($deskripsi) {
                    // Delete old and create new (same as original behavior)
                    $deskripsi->delete();
                    DeskripsiKarier::create([
                        'id_riwayat' => $newData['id_riwayat'],
                        'deskripsi' => $newData['deskripsi'],
                    ]);
                }
                break;

            case 'delete':
                $deskripsi = DeskripsiKarier::find($pending->related_id);
                if ($deskripsi) {
                    $deskripsi->delete();
                }
                break;
        }
    }

    private function applyPortofolio(Alumni $alumni, PendingProfileUpdate $pending): void
    {
        $newData = $pending->new_data;

        switch ($pending->action) {
            case 'create':
                $gambarPath = null;
                if ($pending->gambar_path) {
                    // Move from pending to permanent location
                    $newPath = str_replace('portofolio/pending', 'portofolio', $pending->gambar_path);
                    if (Storage::disk('public')->exists($pending->gambar_path)) {
                        Storage::disk('public')->move($pending->gambar_path, $newPath);
                        $oldThumb = GeneratesThumbnail::thumbnailPath($pending->gambar_path);
                        $newThumb = GeneratesThumbnail::thumbnailPath($newPath);
                        if ($oldThumb && Storage::disk('public')->exists($oldThumb)) {
                            Storage::disk('public')->move($oldThumb, $newThumb);
                        }
                    }
                    $gambarPath = $newPath;
                }
                Portofolio::create([
                    'id_alumni' => $alumni->id_alumni,
                    'judul' => $newData['judul'],
                    'deskripsi' => $newData['deskripsi'] ?? null,
                    'link_project' => $newData['link_project'] ?? null,
                    'gambar' => $gambarPath,
                ]);
                break;

            case 'update':
                $portofolio = Portofolio::find($pending->related_id);
                if ($portofolio) {
                    $updateData = [
                        'judul' => $newData['judul'] ?? $portofolio->judul,
                        'deskripsi' => $newData['deskripsi'] ?? $portofolio->deskripsi,
                        'link_project' => $newData['link_project'] ?? $portofolio->link_project,
                    ];

                    if ($pending->gambar_path) {
                        // Delete old image
                        if ($portofolio->gambar) {
                            $this->deleteWithThumbnail($portofolio->gambar);
                        }
                        $newPath = str_replace('portofolio/pending', 'portofolio', $pending->gambar_path);
                        if (Storage::disk('public')->exists($pending->gambar_path)) {
                            Storage::disk('public')->move($pending->gambar_path, $newPath);
                            $oldThumb = GeneratesThumbnail::thumbnailPath($pending->gambar_path);
                            $newThumb = GeneratesThumbnail::thumbnailPath($newPath);
                            if ($oldThumb && Storage::disk('public')->exists($oldThumb)) {
                                Storage::disk('public')->move($oldThumb, $newThumb);
                            }
                        }
                        $updateData['gambar'] = $newPath;
                    }

                    $portofolio->update($updateData);
                }
                break;

            case 'delete':
                $portofolio = Portofolio::find($pending->related_id);
                if ($portofolio) {
                    if ($portofolio->gambar) {
                        $this->deleteWithThumbnail($portofolio->gambar);
                    }
                    $portofolio->delete();
                }
                break;
        }
    }

    private function getSectionLabel(string $section): string
    {
        return match ($section) {
            'personal_info' => 'Detail Pribadi',
            'skills' => 'Keahlian',
            'social_media' => 'Media Sosial',
            'deskripsi_karier' => 'Deskripsi Karier',
            'portofolio' => 'Portofolio',
            default => 'Profil',
        };
    }
}
