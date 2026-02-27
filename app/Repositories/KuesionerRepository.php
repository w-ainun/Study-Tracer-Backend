<?php

namespace App\Repositories;

use App\Interfaces\KuesionerRepositoryInterface;
use App\Models\Kuesioner;
use App\Models\SectionQues;
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
        $query = Kuesioner::with(['status', 'sectionQues'])
            ->withCount('pertanyaan');

        if (!empty($filters['id_status'])) {
            $query->where('id_status', $filters['id_status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('judul_kuesioner', 'like', "%{$search}%")
                  ->orWhere('deskripsi_kuesioner', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get single kuesioner by ID with full nested relations
     */
    public function getById(int $id)
    {
        return Kuesioner::with(['status', 'sectionQues.pertanyaan.opsiJawaban'])
            ->findOrFail($id);
    }

    /**
     * Create a new kuesioner
     */
    public function create(array $data)
    {
        $kuesioner = Kuesioner::create($data);
        return $kuesioner->load('status');
    }

    /**
     * Update kuesioner
     */
    public function update(int $id, array $data)
    {
        $kuesioner = Kuesioner::findOrFail($id);
        $kuesioner->update($data);
        return $kuesioner->fresh()->load('status');
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
        $query = Pertanyaan::with(['sectionQues.kuesioner.status', 'opsiJawaban']);

        // Filter by kuesioner ID
        if (!empty($filters['id_kuesioner'])) {
            $query->whereHas('sectionQues', function ($q) use ($filters) {
                $q->where('id_kuesioner', $filters['id_kuesioner']);
            });
        }

        // Filter by section ID
        if (!empty($filters['id_sectionques'])) {
            $query->where('id_sectionques', $filters['id_sectionques']);
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
     * Can use existing id_sectionques OR auto-creates section based on judul_bagian
     */
    public function addPertanyaan(int $kuesionerId, array $data)
    {
        // Jika id_sectionques disediakan, gunakan langsung
        if (!empty($data['id_sectionques'])) {
            $sectionId = $data['id_sectionques'];
            
            // Validasi bahwa section ini memang milik kuesioner yang benar
            $section = SectionQues::where('id_sectionques', $sectionId)
                ->where('id_kuesioner', $kuesionerId)
                ->firstOrFail();
        } else {
            // Jika tidak ada id_sectionques, create/find berdasarkan judul_bagian
            $section = SectionQues::firstOrCreate([
                'id_kuesioner' => $kuesionerId,
                'judul_pertanyaan' => $data['judul_bagian'] ?? 'Umum',
            ]);
            $sectionId = $section->id_sectionques;
        }

        $pertanyaan = Pertanyaan::create([
            'id_sectionques' => $sectionId,
            'isi_pertanyaan' => $data['isi_pertanyaan'],
        ]);

        if (!empty($data['opsi'])) {
            foreach ($data['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        return $pertanyaan->load(['opsiJawaban', 'sectionQues']);
    }

    /**
     * Store pertanyaan directly using id_sectionques (no kuesioner ID needed)
     */
    public function storePertanyaan(array $data)
    {
        // id_sectionques harus ada di data
        $sectionId = $data['id_sectionques'];
        
        // Validasi section exists
        $section = SectionQues::findOrFail($sectionId);

        $pertanyaan = Pertanyaan::create([
            'id_sectionques' => $sectionId,
            'isi_pertanyaan' => $data['isi_pertanyaan'],
        ]);

        if (!empty($data['opsi'])) {
            foreach ($data['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        return $pertanyaan->load(['opsiJawaban', 'sectionQues']);
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

        // If id_sectionques provided, update directly
        if (isset($data['id_sectionques'])) {
            $updateData['id_sectionques'] = $data['id_sectionques'];
        }
        // Or if judul_bagian changed, move to different section (legacy support)
        elseif (isset($data['judul_bagian'])) {
            $kuesionerId = $pertanyaan->sectionQues->id_kuesioner;
            $section = SectionQues::firstOrCreate([
                'id_kuesioner' => $kuesionerId,
                'judul_pertanyaan' => $data['judul_bagian'],
            ]);
            $updateData['id_sectionques'] = $section->id_sectionques;
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

        return $pertanyaan->fresh()->load(['opsiJawaban', 'sectionQues']);
    }

    /**
     * Delete pertanyaan and clean up empty sections
     */
    public function deletePertanyaan(int $pertanyaanId)
    {
        $pertanyaan = Pertanyaan::findOrFail($pertanyaanId);
        $sectionId = $pertanyaan->id_sectionques;

        $pertanyaan->delete();

        // Clean up empty sections
        $remaining = Pertanyaan::where('id_sectionques', $sectionId)->count();
        if ($remaining === 0) {
            SectionQues::where('id_sectionques', $sectionId)->delete();
        }

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
        return Kuesioner::with(['status', 'sectionQues.pertanyaan.opsiJawaban'])
            ->orderBy('tanggal_publikasi', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get published kuesioner by status (e.g., kuesioner for "Bekerja")
     */
    public function getPublishedByStatus(int $statusId)
    {
        return Kuesioner::with(['status', 'sectionQues.pertanyaan.opsiJawaban'])
            ->where('id_status', $statusId)
            ->first();
    }

    /**
     * Get kuesioner with full pertanyaan tree
     */
    public function getKuesionerWithPertanyaan(int $kuesionerId)
    {
        return Kuesioner::with(['status', 'sectionQues.pertanyaan.opsiJawaban'])
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
        $kuesioner = Kuesioner::with(['status', 'sectionQues.pertanyaan.opsiJawaban'])->findOrFail($kuesionerId);

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
     * Update kuesioner status (visibility) - DEPRECATED: status moved to pertanyaan
     */
    public function updateKuesionerStatus(int $kuesionerId, string $status)
    {
        // Status now managed at pertanyaan level
        $kuesioner = Kuesioner::findOrFail($kuesionerId);
        return $kuesioner->load(['status', 'sectionQues']);
    }
}
