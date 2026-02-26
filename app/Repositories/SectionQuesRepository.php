<?php

namespace App\Repositories;

use App\Interfaces\SectionQuesRepositoryInterface;
use App\Models\SectionQues;
use App\Models\Pertanyaan;
use App\Models\OpsiJawaban;

class SectionQuesRepository implements SectionQuesRepositoryInterface
{
    public function getAll(int $kuesionerId)
    {
        return SectionQues::where('id_kuesioner', $kuesionerId)
            ->with(['pertanyaan.opsiJawaban'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getById(int $id)
    {
        return SectionQues::with(['pertanyaan.opsiJawaban'])->findOrFail($id);
    }

    public function create(array $data)
    {
        return SectionQues::create($data);
    }

    public function update(int $id, array $data)
    {
        $section = SectionQues::findOrFail($id);
        $section->update($data);
        return $section->fresh();
    }

    public function delete(int $id)
    {
        $section = SectionQues::findOrFail($id);
        $section->delete();
        return true;
    }

    public function addPertanyaan(int $sectionId, array $data)
    {
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

        return $pertanyaan->load('opsiJawaban');
    }
}
