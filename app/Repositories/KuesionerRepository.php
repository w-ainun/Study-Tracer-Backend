<?php

namespace App\Repositories;

use App\Interfaces\KuesionerRepositoryInterface;
use App\Models\Kuesioner;
use App\Models\Pertanyaan;
use App\Models\OpsiJawaban;
use App\Models\Jawaban;

class KuesionerRepository implements KuesionerRepositoryInterface
{
    /**
     * Get all kuesioner with filters (admin view)
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Kuesioner::with(['statusKarir'])
            ->withCount('pertanyaan');

        if (!empty($filters['id_status'])) {
            $query->where('id_status', $filters['id_status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by kuesioner status (hidden/aktif/draft)
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get single kuesioner by ID with full nested relations
     */
    public function getById(int $id)
    {
        return Kuesioner::with(['statusKarir', 'pertanyaan.opsiJawaban'])
            ->findOrFail($id);
    }

    /**
     * Create a new kuesioner
     */
    public function create(array $data)
    {
        $kuesioner = Kuesioner::create($data);
        return $kuesioner->load('statusKarir');
    }

    /**
     * Update kuesioner
     */
    public function update(int $id, array $data)
    {
        $kuesioner = Kuesioner::findOrFail($id);
        $kuesioner->update($data);
        return $kuesioner->fresh()->load('statusKarir');
    }

    /**
     * Delete kuesioner (cascade via DB)
     */
    public function delete(int $id)
    {
        $kuesioner = Kuesioner::findOrFail($id);
        $kuesioner->delete();
        return true;
    }

    /**     * Get all pertanyaan with filters and pagination
     */
    public function getAllPertanyaan(array $filters = [], int $perPage = 15)
    {
        $query = Pertanyaan::with(['kuesioner.statusKarir', 'opsiJawaban']);

        // Filter by kuesioner ID
        if (!empty($filters['id_kuesioner'])) {
            $query->where('id_kuesioner', $filters['id_kuesioner']);
        }

        // Search by pertanyaan text
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('isi_pertanyaan', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Add pertanyaan to a kuesioner
     */
    public function addPertanyaan(int $kuesionerId, array $data)
    {
        // Validate kuesioner exists
        Kuesioner::findOrFail($kuesionerId);

        $pertanyaan = Pertanyaan::create([
            'id_kuesioner' => $kuesionerId,
            'isi_pertanyaan' => $data['isi_pertanyaan'],
            'status_pertanyaan' => $data['status_pertanyaan'] ?? 'draft',
        ]);

        if (!empty($data['opsi'])) {
            foreach ($data['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        return $pertanyaan->load(['opsiJawaban', 'kuesioner']);
    }

    /**
     * Update pertanyaan
     */
    public function updatePertanyaan(int $pertanyaanId, array $data)
    {
        $pertanyaan = Pertanyaan::findOrFail($pertanyaanId);

        $updateData = [];
        if (isset($data['isi_pertanyaan'])) {
            $updateData['isi_pertanyaan'] = $data['isi_pertanyaan'];
        }

        if (isset($data['status_pertanyaan'])) {
            $updateData['status_pertanyaan'] = $data['status_pertanyaan'];
        }

        // If id_kuesioner provided, move to different kuesioner
        if (isset($data['id_kuesioner'])) {
            Kuesioner::findOrFail($data['id_kuesioner']);
            $updateData['id_kuesioner'] = $data['id_kuesioner'];
        }

        $pertanyaan->update($updateData);

        // Replace opsi jawaban if provided
        if (isset($data['opsi'])) {
            OpsiJawaban::where('id_pertanyaan', $pertanyaanId)->delete();
            foreach ($data['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaanId,
                    'opsi' => $opsi,
                ]);
            }
        }

        return $pertanyaan->fresh()->load(['opsiJawaban', 'kuesioner']);
    }

    /**
     * Delete pertanyaan
     */
    public function deletePertanyaan(int $pertanyaanId)
    {
        $pertanyaan = Pertanyaan::findOrFail($pertanyaanId);
        $pertanyaan->delete();
        return true;
    }

    /**
     * Add opsi jawaban to pertanyaan
     */
    public function addOpsiJawaban(int $pertanyaanId, array $opsiList)
    {
        $created = [];
        foreach ($opsiList as $opsi) {
            $created[] = OpsiJawaban::create([
                'id_pertanyaan' => $pertanyaanId,
                'opsi' => $opsi,
            ]);
        }
        return $created;
    }

    /**
     * Submit jawaban from alumni
     */
    public function submitJawaban(int $userId, array $jawabanData)
    {
        $created = [];
        foreach ($jawabanData as $jawaban) {
            $created[] = Jawaban::create([
                'id_pertanyaan' => $jawaban['id_pertanyaan'],
                'id_user' => $userId,
                'id_opsiJawaban' => $jawaban['id_opsiJawaban'] ?? null,
                'jawaban' => $jawaban['jawaban'] ?? null,
            ]);
        }
        return $created;
    }

    /**
     * Get published (aktif) kuesioner for alumni
     */
    public function getPublished(int $perPage = 15)
    {
        return Kuesioner::with(['statusKarir', 'pertanyaan.opsiJawaban'])
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_publikasi')
            ->where(function ($query) {
                $query->whereNull('tanggal_mulai')
                      ->orWhere('tanggal_mulai', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                      ->orWhere('tanggal_selesai', '>=', now());
            })
            ->orderBy('tanggal_publikasi', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get published kuesioner by status (e.g., kuesioner for "Bekerja")
     */
    public function getPublishedByStatus(int $statusId)
    {
        return Kuesioner::with(['statusKarir', 'pertanyaan.opsiJawaban'])
            ->where('id_status', $statusId)
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_publikasi')
            ->where(function ($query) {
                $query->whereNull('tanggal_mulai')
                      ->orWhere('tanggal_mulai', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('tanggal_selesai')
                      ->orWhere('tanggal_selesai', '>=', now());
            })
            ->first();
    }

    /**
     * Get kuesioner with full pertanyaan tree
     */
    public function getKuesionerWithPertanyaan(int $kuesionerId)
    {
        return Kuesioner::with(['statusKarir', 'pertanyaan.opsiJawaban'])
            ->findOrFail($kuesionerId);
    }

    // ═══════════════════════════════════════════════
    //  ADMIN JAWABAN
    // ═══════════════════════════════════════════════

    /**
     * Get list of alumni who answered a kuesioner
     * Optimized: single query with grouping instead of N+1 per-user loop
     */
    public function getAlumniJawaban(int $kuesionerId, array $filters = [])
    {
        $kuesioner = Kuesioner::findOrFail($kuesionerId);

        $pertanyaanIds = $kuesioner->pertanyaan()->pluck('pertanyaan.id_pertanyaan');

        // Single aggregated query: get counts + latest date + status per user
        $jawabanStats = Jawaban::whereIn('id_pertanyaan', $pertanyaanIds)
            ->selectRaw('id_user, COUNT(*) as total_jawaban, MAX(created_at) as tanggal_submit, MAX(status) as status')
            ->groupBy('id_user')
            ->get()
            ->keyBy('id_user');

        $userIds = $jawabanStats->keys()->toArray();

        // Single query to load all users with alumni + jurusan
        $usersQuery = \App\Models\User::with('alumni.jurusan')
            ->whereIn('id_users', $userIds);

        // Apply search filter at DB level
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $usersQuery->whereHas('alumni', function ($q) use ($search) {
                $q->where('nama_alumni', 'like', "%{$search}%");
            });
        }

        $users = $usersQuery->get()->keyBy('id_users');

        $result = [];
        foreach ($users as $userId => $user) {
            $stats = $jawabanStats->get($userId);
            $result[] = [
                'alumni' => [
                    'id' => $user->id_users,
                    'foto' => $user->alumni?->foto,
                    'nama' => $user->alumni?->nama_alumni,
                    'nis' => $user->alumni?->nis ?? null,
                    'nisn' => $user->alumni?->nisn ?? null,
                    'jurusan' => $user->alumni?->jurusan?->nama_jurusan ?? null,
                    'tahun_lulus' => $user->alumni?->tahun_lulus,
                ],
                'total_jawaban' => $stats->total_jawaban,
                'tanggal_submit' => $stats->tanggal_submit,
                'status' => $stats->status ?? 'Belum Selesai',
            ];
        }

        return [
            'kuesioner' => [
                'id' => $kuesioner->id_kuesioner,
                'judul' => $kuesioner->judul_kuesioner,
                'total_pertanyaan' => $pertanyaanIds->count(),
            ],
            'total_responden' => count($result),
            'data' => $result,
        ];
    }

    /**
     * Get detailed jawaban from a specific alumni
     */
    public function getAlumniJawabanDetail(int $kuesionerId, int $alumniId)
    {
        $kuesioner = Kuesioner::with(['statusKarir', 'pertanyaan.opsiJawaban'])->findOrFail($kuesionerId);

        $pertanyaanIds = $kuesioner->pertanyaan()->pluck('pertanyaan.id_pertanyaan');

        $jawaban = Jawaban::where('id_user', $alumniId)
            ->whereIn('id_pertanyaan', $pertanyaanIds)
            ->with(['pertanyaan.opsiJawaban', 'opsiJawaban'])
            ->get();

        $user = \App\Models\User::with('alumni.jurusan')->find($alumniId);

        return [
            'alumni' => [
                'id' => $user?->id_users,
                'nama' => $user?->alumni?->nama_alumni,
                'nis' => $user?->alumni?->nis ?? null,
                'nisn' => $user?->alumni?->nisn ?? null,
                'jurusan' => $user?->alumni?->jurusan?->nama_jurusan ?? null,
                'tahun_lulus' => $user?->alumni?->tahun_lulus,
            ],
            'kuesioner' => [
                'id' => $kuesioner->id_kuesioner,
                'judul' => $kuesioner->judul_kuesioner,
                'status_nama' => $kuesioner->status?->nama_status,
            ],
            'jawaban' => $jawaban,
        ];
    }

    /**
     * Update kuesioner status (visibility: hidden/aktif/draft)
     */
    public function updateKuesionerStatus(int $kuesionerId, string $status)
    {
        $kuesioner = Kuesioner::findOrFail($kuesionerId);
        $kuesioner->update(['status' => $status]);
        return $kuesioner->fresh()->load('statusKarir');
    }
}
