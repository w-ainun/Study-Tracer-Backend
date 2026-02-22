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
        $pertanyaan->update(['pertanyaan' => $data['pertanyaan']]);

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
}
