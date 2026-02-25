<?php

namespace App\Repositories;

use App\Interfaces\KuesionerRepositoryInterface;
use App\Models\Kuesioner;
use App\Models\PertanyaanKuesioner;
use App\Models\OpsiJawaban;
use App\Models\JawabanKuesioner;

class KuesionerRepository implements KuesionerRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Kuesioner::withCount('pertanyaan');

        if (!empty($filters['status_kuesioner'])) {
            $query->where('status_kuesioner', $filters['status_kuesioner']);
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

    public function getById(int $id)
    {
        return Kuesioner::with(['pertanyaan.opsiJawaban'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return Kuesioner::create($data);
    }

    public function update(int $id, array $data)
    {
        $kuesioner = Kuesioner::findOrFail($id);
        $kuesioner->update($data);
        return $kuesioner->fresh();
    }

    public function delete(int $id)
    {
        $kuesioner = Kuesioner::findOrFail($id);
        $kuesioner->delete();
        return true;
    }

    public function addPertanyaan(int $kuesionerId, array $data)
    {
        $pertanyaan = PertanyaanKuesioner::create([
            'id_kuesioner' => $kuesionerId,
            'pertanyaan' => $data['pertanyaan'],
            'tipe_pertanyaan' => $data['tipe_pertanyaan'] ?? 'pilihan_tunggal',
            'status_pertanyaan' => $data['status_pertanyaan'] ?? 'DRAF',
            'kategori' => $data['kategori'] ?? null,
            'judul_bagian' => $data['judul_bagian'] ?? null,
            'urutan' => $data['urutan'] ?? 0,
        ]);

        if (!empty($data['opsi'])) {
            foreach ($data['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaanKuis,
                    'opsi' => $opsi,
                ]);
            }
        }

        return $pertanyaan->load('opsiJawaban');
    }

    public function updatePertanyaan(int $pertanyaanId, array $data)
    {
        $pertanyaan = PertanyaanKuesioner::findOrFail($pertanyaanId);

        $updateData = ['pertanyaan' => $data['pertanyaan']];
        foreach (['tipe_pertanyaan', 'status_pertanyaan', 'kategori', 'judul_bagian', 'urutan'] as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        $pertanyaan->update($updateData);

        if (isset($data['opsi'])) {
            // Remove old options and add new ones
            OpsiJawaban::where('id_pertanyaan', $pertanyaanId)->delete();
            foreach ($data['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaanId,
                    'opsi' => $opsi,
                ]);
            }
        }

        return $pertanyaan->load('opsiJawaban');
    }

    public function deletePertanyaan(int $pertanyaanId)
    {
        $pertanyaan = PertanyaanKuesioner::findOrFail($pertanyaanId);
        $pertanyaan->delete();
        return true;
    }

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

    public function submitJawaban(int $userId, array $jawabanData)
    {
        $created = [];
        foreach ($jawabanData as $jawaban) {
            $created[] = JawabanKuesioner::create([
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
        return Kuesioner::with(['pertanyaan.opsiJawaban'])
            ->where('status_kuesioner', 'publish')
            ->orderBy('tanggal_publikasi', 'desc')
            ->paginate($perPage);
    }

    public function getKuesionerWithPertanyaan(int $kuesionerId)
    {
        return Kuesioner::with(['pertanyaan.opsiJawaban'])
            ->findOrFail($kuesionerId);
    }

    // ═══════════════════════════════════════════════
    //  ADMIN JAWABAN
    // ═══════════════════════════════════════════════

    public function getAlumniJawaban(int $kuesionerId, array $filters = [])
    {
        $kuesioner = Kuesioner::findOrFail($kuesionerId);

        $pertanyaanIds = $kuesioner->pertanyaan()->pluck('id_pertanyaanKuis');

        $query = JawabanKuesioner::whereIn('id_pertanyaan', $pertanyaanIds)
            ->with(['user.alumni', 'pertanyaan', 'opsiJawaban'])
            ->select('id_user')
            ->groupBy('id_user');

        // Get grouped alumni who answered
        $userIds = JawabanKuesioner::whereIn('id_pertanyaan', $pertanyaanIds)
            ->distinct()
            ->pluck('id_user');

        $result = [];
        foreach ($userIds as $userId) {
            $jawaban = JawabanKuesioner::where('id_user', $userId)
                ->whereIn('id_pertanyaan', $pertanyaanIds)
                ->with(['pertanyaan.opsiJawaban', 'opsiJawaban'])
                ->get();

            $user = \App\Models\User::with('alumni')->find($userId);

            $result[] = [
                'alumni' => [
                    'id' => $user?->id_users,
                    'nama' => $user?->alumni?->nama_depan . ' ' . $user?->alumni?->nama_belakang,
                    'nim' => $user?->alumni?->nim ?? null,
                ],
                'total_jawaban' => $jawaban->count(),
                'tanggal_submit' => $jawaban->first()?->created_at,
            ];
        }

        return $result;
    }

    public function getAlumniJawabanDetail(int $kuesionerId, int $alumniId)
    {
        $kuesioner = Kuesioner::with('pertanyaan.opsiJawaban')->findOrFail($kuesionerId);

        $pertanyaanIds = $kuesioner->pertanyaan()->pluck('id_pertanyaanKuis');

        $jawaban = JawabanKuesioner::where('id_user', $alumniId)
            ->whereIn('id_pertanyaan', $pertanyaanIds)
            ->with(['pertanyaan.opsiJawaban', 'opsiJawaban'])
            ->get();

        $user = \App\Models\User::with('alumni')->find($alumniId);

        return [
            'alumni' => [
                'id' => $user?->id_users,
                'nama' => $user?->alumni?->nama_depan . ' ' . $user?->alumni?->nama_belakang,
                'nim' => $user?->alumni?->nim ?? null,
            ],
            'kuesioner' => [
                'id' => $kuesioner->id_kuesioner,
                'judul' => $kuesioner->judul_kuesioner,
            ],
            'jawaban' => $jawaban,
        ];
    }

    public function updatePertanyaanStatus(int $pertanyaanId, string $status)
    {
        $pertanyaan = PertanyaanKuesioner::findOrFail($pertanyaanId);
        $pertanyaan->update(['status_pertanyaan' => $status]);
        return $pertanyaan->fresh()->load('opsiJawaban');
    }
}
