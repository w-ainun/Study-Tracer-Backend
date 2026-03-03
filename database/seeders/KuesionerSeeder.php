<?php

namespace Database\Seeders;

use App\Models\Kuesioner;
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
            'title' => 'Kuesioner Alumni yang Bekerja',
            'deskripsi' => 'Kuesioner untuk mengumpulkan data dan feedback dari alumni yang sudah bekerja mengenai kepuasan pendidikan, informasi karier, dan penilaian umum.',
            'status' => 'aktif',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        // Pertanyaan untuk Kuesioner 1: Kepuasan Pendidikan
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
                'id_kuesioner' => $kuesioner->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Pertanyaan untuk Kuesioner 1: Informasi Karier
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
                'id_kuesioner' => $kuesioner->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Pertanyaan untuk Kuesioner 1: Penilaian Umum
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
                'id_kuesioner' => $kuesioner->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
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
            'title' => 'Kuesioner Alumni yang Melanjutkan Kuliah',
            'deskripsi' => 'Kuesioner untuk alumni yang melanjutkan pendidikan ke jenjang yang lebih tinggi, mencakup informasi studi lanjut dan kebutuhan pengembangan.',
            'status' => 'draft',
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'tanggal_publikasi' => null,
        ]);

        // Pertanyaan untuk Kuesioner 2: Informasi Studi Lanjut
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
                'id_kuesioner' => $kuesioner2->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Pertanyaan untuk Kuesioner 2: Pengembangan Diri
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
                'id_kuesioner' => $kuesioner2->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
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
            'title' => 'Kuesioner Alumni Wirausaha',
            'deskripsi' => 'Kuesioner untuk alumni yang menjalankan usaha sendiri, mencakup informasi usaha dan bagaimana pendidikan membantu dalam berwirausaha.',
            'status' => 'aktif',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        // Pertanyaan untuk Kuesioner 3: Informasi Usaha
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

        foreach ($pertanyaanSection3_1 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_kuesioner' => $kuesioner3->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // Kuesioner 4: Tambahan
        $kuesioner4 = Kuesioner::create([
            'id_status' => 4, // Status lainnya
            'title' => 'Kuesioner Tambahan',
            'deskripsi' => 'Kuesioner tambahan untuk keperluan survei lainnya.',
            'status' => 'hidden',
            'tanggal_mulai' => null,
            'tanggal_selesai' => null,
            'tanggal_publikasi' => null,
        ]);
    }
}
