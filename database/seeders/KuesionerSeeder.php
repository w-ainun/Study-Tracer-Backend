<?php

namespace Database\Seeders;

use App\Models\Kuesioner;
use App\Models\PertanyaanKuesioner;
use App\Models\OpsiJawaban;
use Illuminate\Database\Seeder;

class KuesionerSeeder extends Seeder
{
    public function run(): void
    {
        $kuesioner = Kuesioner::create([
            'judul_kuesioner' => 'Kuesoner Pekerja',
            'deskripsi_kuesioner' => 'Kuesioner untuk mengetahui tingkat kepuasan alumni terhadap pendidikan yang diterima.',
            'status_kuesioner' => 'publish',
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        $pertanyaanData = [
            [
                'pertanyaan' => 'Seberapa puas Anda dengan kualitas pendidikan yang diterima?',
                'tipe_pertanyaan' => 'pilihan_tunggal',
                'status_pertanyaan' => 'TERLIHAT',
                'kategori' => 'Kepuasan',
                'urutan' => 1,
                'opsi' => ['Sangat Puas', 'Puas', 'Cukup', 'Tidak Puas', 'Sangat Tidak Puas'],
            ],
            [
                'pertanyaan' => 'Apakah ilmu yang didapat relevan dengan pekerjaan Anda saat ini?',
                'tipe_pertanyaan' => 'pilihan_tunggal',
                'status_pertanyaan' => 'TERLIHAT',
                'kategori' => 'Relevansi',
                'urutan' => 2,
                'opsi' => ['Sangat Relevan', 'Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
            ],
            [
                'pertanyaan' => 'Berapa lama waktu yang dibutuhkan untuk mendapatkan pekerjaan pertama setelah lulus?',
                'tipe_pertanyaan' => 'pilihan_tunggal',
                'status_pertanyaan' => 'TERLIHAT',
                'kategori' => 'Karier',
                'urutan' => 3,
                'opsi' => ['< 3 bulan', '3-6 bulan', '6-12 bulan', '> 12 bulan', 'Belum bekerja'],
            ],
            [
                'pertanyaan' => 'Bagaimana fasilitas belajar di sekolah menurut Anda?',
                'tipe_pertanyaan' => 'skala',
                'status_pertanyaan' => 'TERLIHAT',
                'kategori' => 'Fasilitas',
                'urutan' => 4,
                'opsi' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
            ],
            [
                'pertanyaan' => 'Apakah Anda merekomendasikan sekolah ini kepada orang lain?',
                'tipe_pertanyaan' => 'pilihan_tunggal',
                'status_pertanyaan' => 'TERLIHAT',
                'kategori' => 'Umum',
                'urutan' => 5,
                'opsi' => ['Sangat Merekomendasikan', 'Merekomendasikan', 'Netral', 'Tidak Merekomendasikan', 'Sangat Tidak Merekomendasikan'],
            ],
        ];

        foreach ($pertanyaanData as $item) {
            $pertanyaan = PertanyaanKuesioner::create([
                'id_kuesioner' => $kuesioner->id_kuesioner,
                'pertanyaan' => $item['pertanyaan'],
                'tipe_pertanyaan' => $item['tipe_pertanyaan'],
                'status_pertanyaan' => $item['status_pertanyaan'],
                'kategori' => $item['kategori'],
                'urutan' => $item['urutan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaanKuis,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Create second questionnaire (draft)
        $kuesioner2 = Kuesioner::create([
            'judul_kuesioner' => 'Kuesoner Kuliah',
            'deskripsi_kuesioner' => 'Kuesioner untuk mengetahui kebutuhan pelatihan alumni.',
            'status_kuesioner' => 'draft',
            'tanggal_publikasi' => null,
        ]);

        $pertanyaanData2 = [
            [
                'pertanyaan' => 'Pelatihan apa yang paling Anda butuhkan?',
                'tipe_pertanyaan' => 'pilihan_ganda',
                'status_pertanyaan' => 'DRAF',
                'kategori' => 'Pelatihan',
                'urutan' => 1,
                'opsi' => ['Programming', 'Digital Marketing', 'Desain Grafis', 'Bahasa Asing', 'Soft Skills'],
            ],
            [
                'pertanyaan' => 'Format pelatihan yang Anda sukai?',
                'tipe_pertanyaan' => 'pilihan_tunggal',
                'status_pertanyaan' => 'DRAF',
                'kategori' => 'Pelatihan',
                'urutan' => 2,
                'opsi' => ['Online', 'Offline', 'Hybrid'],
            ],
        ];

        foreach ($pertanyaanData2 as $item) {
            $pertanyaan = PertanyaanKuesioner::create([
                'id_kuesioner' => $kuesioner2->id_kuesioner,
                'pertanyaan' => $item['pertanyaan'],
                'tipe_pertanyaan' => $item['tipe_pertanyaan'],
                'status_pertanyaan' => $item['status_pertanyaan'],
                'kategori' => $item['kategori'],
                'urutan' => $item['urutan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaanKuis,
                    'opsi' => $opsi,
                ]);
            }
        }
    }
}
