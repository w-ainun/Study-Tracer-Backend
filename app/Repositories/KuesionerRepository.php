<?php

namespace App\Repositories;

use App\Interfaces\KuesionerRepositoryInterface;
use App\Models\Kuesioner;
use App\Models\Pertanyaan;
use App\Models\OpsiJawaban;
use App\Models\Jawaban;

class KuesionerRepository implements KuesionerRepositoryInterface
{

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
        // Extract questions if present
        $questions = $data['questions'] ?? [];
        unset($data['questions']);
        
        // Create kuesioner
        $kuesioner = Kuesioner::create($data);
        
        // Create pertanyaan and opsi jawaban if questions provided
        if (!empty($questions)) {
            foreach ($questions as $questionData) {
                $pertanyaan = Pertanyaan::create([
                    'id_kuesioner' => $kuesioner->id_kuesioner,
                    'isi_pertanyaan' => $questionData['text'],
                ]);
                
                // Create opsi jawaban if options provided
                if (!empty($questionData['options'])) {
                    foreach ($questionData['options'] as $opsi) {
                        OpsiJawaban::create([
                            'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                            'opsi' => $opsi,
                        ]);
                    }
                }
            }
        }
        
        return $kuesioner->load('statusKarir', 'pertanyaan.opsiJawaban');
    }

    /**
     * Update kuesioner
     */
    public function update(int $id, array $data)
    {
        // Extract questions if present
        $questions = $data['questions'] ?? [];
        unset($data['questions']);
        
        $kuesioner = Kuesioner::findOrFail($id);
        $kuesioner->update($data);
        
        // Update pertanyaan and opsi jawaban if questions provided
        if (!empty($questions)) {
            // Get existing pertanyaan IDs
            $existingPertanyaanIds = $kuesioner->pertanyaan->pluck('id_pertanyaan')->toArray();
            $updatedPertanyaanIds = [];
            
            foreach ($questions as $questionData) {
                if (isset($questionData['id']) && in_array($questionData['id'], $existingPertanyaanIds)) {
                    // Update existing pertanyaan
                    $pertanyaan = Pertanyaan::find($questionData['id']);
                    $pertanyaan->update([
                        'isi_pertanyaan' => $questionData['text'],
                    ]);
                    
                    $updatedPertanyaanIds[] = $questionData['id'];
                    
                    // Update opsi jawaban
                    if (!empty($questionData['options'])) {
                        // Delete existing opsi
                        OpsiJawaban::where('id_pertanyaan', $pertanyaan->id_pertanyaan)->delete();
                        
                        // Create new opsi
                        foreach ($questionData['options'] as $opsi) {
                            OpsiJawaban::create([
                                'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                                'opsi' => $opsi,
                            ]);
                        }
                    }
                } else {
                    // Create new pertanyaan
                    $pertanyaan = Pertanyaan::create([
                        'id_kuesioner' => $kuesioner->id_kuesioner,
                        'isi_pertanyaan' => $questionData['text'],
                    ]);
                    
                    $updatedPertanyaanIds[] = $pertanyaan->id_pertanyaan;
                    
                    // Create opsi jawaban
                    if (!empty($questionData['options'])) {
                        foreach ($questionData['options'] as $opsi) {
                            OpsiJawaban::create([
                                'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                                'opsi' => $opsi,
                            ]);
                        }
                    }
                }
            }
            
            // Delete pertanyaan that are no longer in the update
            $pertanyaanToDelete = array_diff($existingPertanyaanIds, $updatedPertanyaanIds);
            if (!empty($pertanyaanToDelete)) {
                Pertanyaan::whereIn('id_pertanyaan', $pertanyaanToDelete)->delete();
            }
        }
        
        return $kuesioner->fresh()->load('statusKarir', 'pertanyaan.opsiJawaban');
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
     * Store pertanyaan directly (used for direct creation)
     */
    public function storePertanyaan(array $data)
    {
        // id_kuesioner harus ada di data
        $kuesionerId = $data['id_kuesioner'];
        
        // Validasi kuesioner exists
        Kuesioner::findOrFail($kuesionerId);

        $pertanyaan = Pertanyaan::create([
            'id_kuesioner' => $kuesionerId,
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

    public function getKuesionerWithPertanyaan(int $kuesionerId)
    {
        return Kuesioner::with(['statusKarir', 'pertanyaan.opsiJawaban'])
            ->findOrFail($kuesionerId);
    }

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
                'judul' => $kuesioner->title,
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

        // Get user with complete alumni data
        $user = \App\Models\User::with(['alumni.jurusan'])->findOrFail($alumniId);

        // Get all jawaban from this user for this kuesioner
        $pertanyaanIds = $kuesioner->pertanyaan->pluck('id_pertanyaan');
        
        $jawabanCollection = Jawaban::where('id_user', $alumniId)
            ->whereIn('id_pertanyaan', $pertanyaanIds)
            ->with(['pertanyaan.opsiJawaban', 'opsiJawaban'])
            ->get()
            ->keyBy('id_pertanyaan');

        // Build structured response with all pertanyaan and their jawaban
        $pertanyaanWithJawaban = [];
        foreach ($kuesioner->pertanyaan as $pertanyaan) {
            $jawaban = $jawabanCollection->get($pertanyaan->id_pertanyaan);
            
            $pertanyaanWithJawaban[] = [
                'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                'isi_pertanyaan' => $pertanyaan->isi_pertanyaan,
                'opsi_jawaban' => $pertanyaan->opsiJawaban->map(function ($opsi) {
                    return [
                        'id_opsi' => $opsi->id_opsi,
                        'opsi' => $opsi->opsi,
                    ];
                }),
                'jawaban' => $jawaban ? [
                    'id_jawaban' => $jawaban->id_jawaban,
                    'jawaban_text' => $jawaban->jawaban, // For essay/text questions
                    'opsi_dipilih' => $jawaban->opsiJawaban ? [
                        'id_opsi' => $jawaban->opsiJawaban->id_opsi,
                        'opsi' => $jawaban->opsiJawaban->opsi,
                    ] : null,
                    'created_at' => $jawaban->created_at,
                    'status' => $jawaban->status,
                ] : null,
            ];
        }

        return [
            'alumni' => [
                'id' => $user->id_users,
                'nama' => $user->alumni?->nama_alumni,
                'nis' => $user->alumni?->nis ?? null,
                'nisn' => $user->alumni?->nisn ?? null,
                'email' => $user->email,
                'foto' => $user->alumni?->foto,
                'no_hp' => $user->alumni?->no_hp,
                'alamat' => $user->alumni?->alamat,
                'jenis_kelamin' => $user->alumni?->jenis_kelamin,
                'tempat_lahir' => $user->alumni?->tempat_lahir,
                'tanggal_lahir' => $user->alumni?->tanggal_lahir,
                'jurusan' => $user->alumni?->jurusan?->nama_jurusan ?? null,
                'tahun_masuk' => $user->alumni?->tahun_masuk,
                'tahun_lulus' => $user->alumni?->tahun_lulus,
            ],
            'kuesioner' => [
                'id' => $kuesioner->id_kuesioner,
                'judul' => $kuesioner->title,
                'deskripsi' => $kuesioner->deskripsi,
                'status_karir' => $kuesioner->statusKarir?->nama_status ?? null,
                'total_pertanyaan' => $kuesioner->pertanyaan->count(),
                'tanggal_publikasi' => $kuesioner->tanggal_publikasi,
            ],
            'pertanyaan' => $pertanyaanWithJawaban,
            'statistik' => [
                'total_pertanyaan' => count($pertanyaanWithJawaban),
                'terjawab' => $jawabanCollection->count(),
                'belum_dijawab' => count($pertanyaanWithJawaban) - $jawabanCollection->count(),
                'persentase_selesai' => count($pertanyaanWithJawaban) > 0 
                    ? round(($jawabanCollection->count() / count($pertanyaanWithJawaban)) * 100, 2) 
                    : 0,
            ],
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
