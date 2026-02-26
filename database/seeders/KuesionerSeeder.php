<?php

namespace Database\Seeders;

use App\Models\Kuesioner;
use App\Models\SectionQues;
use App\Models\Pertanyaan;
use App\Models\OpsiJawaban;
use Illuminate\Database\Seeder;

class KuesionerSeeder extends Seeder
{
    public function run(): void
    {
        // Kuesioner 1: Untuk Alumni yang Bekerja
        $kuesioner = Kuesioner::create([
            'id_status' => 1, // Bekerja
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        // Section 1: Kepuasan Pendidikan
        $section1 = SectionQues::create([
            'id_kuesioner' => $kuesioner->id_kuesioner,
            'judul_pertanyaan' => 'Kepuasan Pendidikan',
        ]);

        $pertanyaanSection1 = [
            [
                'isi_pertanyaan' => 'Seberapa puas Anda dengan kualitas pendidikan yang diterima?',
                'opsi' => ['Sangat Puas', 'Puas', 'Cukup', 'Tidak Puas', 'Sangat Tidak Puas'],
            ],
            [
                'isi_pertanyaan' => 'Apakah ilmu yang didapat relevan dengan pekerjaan Anda saat ini?',
                'opsi' => ['Sangat Relevan', 'Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
            ],
        ];

        foreach ($pertanyaanSection1 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_sectionques' => $section1->id_sectionques,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
                'status_pertanyaan' => 'publish',
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Section 2: Karier
        $section2 = SectionQues::create([
            'id_kuesioner' => $kuesioner->id_kuesioner,
            'judul_pertanyaan' => 'Informasi Karier',
        ]);

        $pertanyaanSection2 = [
            [
                'isi_pertanyaan' => 'Berapa lama waktu yang dibutuhkan untuk mendapatkan pekerjaan pertama setelah lulus?',
                'opsi' => ['< 3 bulan', '3-6 bulan', '6-12 bulan', '> 12 bulan', 'Masih mencari'],
            ],
            [
                'isi_pertanyaan' => 'Bagaimana Anda mendapatkan pekerjaan saat ini?',
                'opsi' => ['Bursa Kerja', 'Referensi', 'Job Portal', 'Langsung dari Perusahaan', 'Lainnya'],
            ],
        ];

        foreach ($pertanyaanSection2 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_sectionques' => $section2->id_sectionques,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
                'status_pertanyaan' => 'publish',
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Section 3: Umum
        $section3 = SectionQues::create([
            'id_kuesioner' => $kuesioner->id_kuesioner,
            'judul_pertanyaan' => 'Penilaian Umum',
        ]);

        $pertanyaanSection3 = [
            [
                'isi_pertanyaan' => 'Bagaimana fasilitas belajar di sekolah menurut Anda?',
                'opsi' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
            ],
            [
                'isi_pertanyaan' => 'Apakah Anda merekomendasikan sekolah ini kepada orang lain?',
                'opsi' => ['Sangat Merekomendasikan', 'Merekomendasikan', 'Netral', 'Tidak Merekomendasikan', 'Sangat Tidak Merekomendasikan'],
            ],
        ];

        foreach ($pertanyaanSection3 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_sectionques' => $section3->id_sectionques,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
                'status_pertanyaan' => 'publish',
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }


        // Kuesioner 2: Untuk Alumni yang Kuliah (Draft)
        $kuesioner2 = Kuesioner::create([
            'id_status' => 2, // Kuliah
            'tanggal_publikasi' => null,
        ]);

        // Section 1: Studi Lanjut
        $section2_1 = SectionQues::create([
            'id_kuesioner' => $kuesioner2->id_kuesioner,
            'judul_pertanyaan' => 'Informasi Studi Lanjut',
        ]);

        $pertanyaanSection2_1 = [
            [
                'isi_pertanyaan' => 'Program studi apa yang Anda ambil saat ini?',
                'opsi' => [],
            ],
            [
                'isi_pertanyaan' => 'Apakah program studi Anda relevan dengan pendidikan sebelumnya?',
                'opsi' => ['Sangat Relevan', 'Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
            ],
        ];

        foreach ($pertanyaanSection2_1 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_sectionques' => $section2_1->id_sectionques,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
                'status_pertanyaan' => 'draft',
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Section 2: Kebutuhan Pengembangan
        $section2_2 = SectionQues::create([
            'id_kuesioner' => $kuesioner2->id_kuesioner,
            'judul_pertanyaan' => 'Pengembangan Diri',
        ]);

        $pertanyaanSection2_2 = [
            [
                'isi_pertanyaan' => 'Pelatihan apa yang paling Anda butuhkan untuk menunjang studi?',
                'opsi' => ['Programming', 'Digital Marketing', 'Desain Grafis', 'Bahasa Asing', 'Soft Skills'],
            ],
            [
                'isi_pertanyaan' => 'Format pelatihan yang Anda sukai?',
                'opsi' => ['Online', 'Offline', 'Hybrid'],
            ],
        ];

        foreach ($pertanyaanSection2_2 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_sectionques' => $section2_2->id_sectionques,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
                'status_pertanyaan' => 'draft',
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Kuesioner 3: Untuk Alumni Wirausaha
        $kuesioner3 = Kuesioner::create([
            'id_status' => 3, // Wirausaha
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        // Section 1: Informasi Usaha
        $section3_1 = SectionQues::create([
            'id_kuesioner' => $kuesioner3->id_kuesioner,
            'judul_pertanyaan' => 'Informasi Usaha',
        ]);

        $pertanyaanSection3_1 = [
            [
                'isi_pertanyaan' => 'Bidang usaha apa yang Anda jalankan?',
                'opsi' => [],
            ],
            [
                'isi_pertanyaan' => 'Berapa lama usaha Anda sudah berjalan?',
                'opsi' => ['< 6 bulan', '6-12 bulan', '1-2 tahun', '> 2 tahun'],
            ],
            [
                'isi_pertanyaan' => 'Apakah pendidikan yang Anda terima membantu dalam menjalankan usaha?',
                'opsi' => ['Sangat Membantu', 'Membantu', 'Cukup Membantu', 'Kurang Membantu', 'Tidak Membantu'],
            ],
        ];

        $kuesioner4 = Kuesioner::create([
            'id_status' => 4, // Kuliah
            'tanggal_publikasi' => null,
        ]);

        foreach ($pertanyaanSection3_1 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_sectionques' => $section3_1->id_sectionques,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
                'status_pertanyaan' => 'publish',
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }
    }
}
