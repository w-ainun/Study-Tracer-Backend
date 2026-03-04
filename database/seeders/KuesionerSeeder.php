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
        // =============================================
        // KUESIONER 1: UNTUK ALUMNI YANG BEKERJA
        // =============================================
        $kuesioner1 = Kuesioner::create([
            'id_status' => 1, // Bekerja
            'title' => 'Kuesioner Alumni yang Bekerja',
            'deskripsi' => 'Kuesioner untuk mengumpulkan data dan feedback dari alumni yang sudah bekerja mengenai kepuasan pendidikan, informasi karier, dan penilaian umum.',
            'status' => 'aktif',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        $pertanyaan1 = [
            [
                'isi_pertanyaan' => 'Seberapa puas Anda dengan kualitas pendidikan yang diterima?',
                'opsi' => ['Sangat Puas', 'Puas', 'Cukup', 'Tidak Puas', 'Sangat Tidak Puas'],
            ],
            [
                'isi_pertanyaan' => 'Apakah ilmu yang didapat relevan dengan pekerjaan Anda saat ini?',
                'opsi' => ['Sangat Relevan', 'Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
            ],
            [
                'isi_pertanyaan' => 'Berapa lama waktu yang dibutuhkan untuk mendapatkan pekerjaan pertama setelah lulus?',
                'opsi' => ['< 3 bulan', '3-6 bulan', '6-12 bulan', '> 12 bulan', 'Masih mencari'],
            ],
            [
                'isi_pertanyaan' => 'Bagaimana Anda mendapatkan pekerjaan saat ini?',
                'opsi' => ['Bursa Kerja', 'Referensi', 'Job Portal', 'Langsung dari Perusahaan', 'Lainnya'],
            ],
            [
                'isi_pertanyaan' => 'Seberapa besar gaji yang Anda terima saat ini?',
                'opsi' => ['< Rp 3 juta', 'Rp 3-5 juta', 'Rp 5-8 juta', 'Rp 8-12 juta', '> Rp 12 juta'],
            ],
        ];

        foreach ($pertanyaan1 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_kuesioner' => $kuesioner1->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // =============================================
        // KUESIONER 2: UNTUK ALUMNI YANG KULIAH
        // =============================================
        $kuesioner2 = Kuesioner::create([
            'id_status' => 2, // Kuliah
            'title' => 'Kuesioner Alumni yang Melanjutkan Kuliah',
            'deskripsi' => 'Kuesioner untuk alumni yang melanjutkan pendidikan ke jenjang yang lebih tinggi, mencakup informasi studi lanjut dan kebutuhan pengembangan.',
            'status' => 'aktif',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        $pertanyaan2 = [
            [
                'isi_pertanyaan' => 'Jenjang pendidikan apa yang Anda ambil saat ini?',
                'opsi' => ['D3', 'D4/S1', 'S2', 'S3', 'Lainnya'],
            ],
            [
                'isi_pertanyaan' => 'Apakah program studi Anda relevan dengan pendidikan sebelumnya?',
                'opsi' => ['Sangat Relevan', 'Relevan', 'Cukup Relevan', 'Kurang Relevan', 'Tidak Relevan'],
            ],
            [
                'isi_pertanyaan' => 'Bagaimana cara Anda membiayai kuliah?',
                'opsi' => ['Orangtua', 'Beasiswa', 'Pinjaman', 'Biaya Sendiri', 'Lainnya'],
            ],
            [
                'isi_pertanyaan' => 'Pelatihan apa yang paling Anda butuhkan untuk menunjang studi?',
                'opsi' => ['Programming', 'Digital Marketing', 'Desain Grafis', 'Bahasa Asing', 'Soft Skills'],
            ],
            [
                'isi_pertanyaan' => 'Apakah Anda berencana bekerja sambil kuliah?',
                'opsi' => ['Ya, sudah bekerja', 'Ya, berencana bekerja', 'Tidak', 'Belum tahu', 'Part-time'],
            ],
        ];

        foreach ($pertanyaan2 as $item) {
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

        // =============================================
        // KUESIONER 3: UNTUK ALUMNI WIRAUSAHA
        // =============================================
        $kuesioner3 = Kuesioner::create([
            'id_status' => 3, // Wirausaha
            'title' => 'Kuesioner Alumni Wirausaha',
            'deskripsi' => 'Kuesioner untuk alumni yang menjalankan usaha sendiri, mencakup informasi usaha dan bagaimana pendidikan membantu dalam berwirausaha.',
            'status' => 'aktif',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        $pertanyaan3 = [
            [
                'isi_pertanyaan' => 'Bidang usaha apa yang Anda jalankan?',
                'opsi' => ['Kuliner', 'Fashion', 'Teknologi', 'Jasa', 'Perdagangan'],
            ],
            [
                'isi_pertanyaan' => 'Berapa lama usaha Anda sudah berjalan?',
                'opsi' => ['< 6 bulan', '6-12 bulan', '1-2 tahun', '2-5 tahun', '> 5 tahun'],
            ],
            [
                'isi_pertanyaan' => 'Apakah pendidikan yang Anda terima membantu dalam menjalankan usaha?',
                'opsi' => ['Sangat Membantu', 'Membantu', 'Cukup Membantu', 'Kurang Membantu', 'Tidak Membantu'],
            ],
            [
                'isi_pertanyaan' => 'Berapa jumlah karyawan yang Anda miliki?',
                'opsi' => ['Tidak ada', '1-5 orang', '6-10 orang', '11-20 orang', '> 20 orang'],
            ],
            [
                'isi_pertanyaan' => 'Apa tantangan terbesar dalam menjalankan usaha?',
                'opsi' => ['Modal', 'SDM', 'Pemasaran', 'Kompetisi', 'Regulasi'],
            ],
        ];

        foreach ($pertanyaan3 as $item) {
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

        // =============================================
        // KUESIONER 4: UNTUK ALUMNI BELUM BEKERJA
        // =============================================
        $kuesioner4 = Kuesioner::create([
            'id_status' => 4, // Belum Bekerja
            'title' => 'Kuesioner Alumni Belum Bekerja',
            'deskripsi' => 'Kuesioner untuk alumni yang belum bekerja, untuk memahami tantangan dan kebutuhan mereka dalam mencari pekerjaan.',
            'status' => 'aktif',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        $pertanyaan4 = [
            [
                'isi_pertanyaan' => 'Apa alasan utama Anda belum bekerja?',
                'opsi' => ['Mencari pekerjaan', 'Persiapan kuliah', 'Mengurus keluarga', 'Sakit/Kesehatan', 'Lainnya'],
            ],
            [
                'isi_pertanyaan' => 'Sudah berapa lama Anda mencari pekerjaan?',
                'opsi' => ['Belum mencari', '< 3 bulan', '3-6 bulan', '6-12 bulan', '> 12 bulan'],
            ],
            [
                'isi_pertanyaan' => 'Apa kendala yang Anda hadapi dalam mencari pekerjaan?',
                'opsi' => ['Kurang pengalaman', 'Keterampilan kurang', 'Lowongan sedikit', 'Gaji tidak sesuai', 'Lainnya'],
            ],
            [
                'isi_pertanyaan' => 'Pelatihan apa yang Anda butuhkan untuk meningkatkan peluang kerja?',
                'opsi' => ['Soft Skills', 'Technical Skills', 'Bahasa Inggris', 'Digital Marketing', 'Programming'],
            ],
            [
                'isi_pertanyaan' => 'Apakah Anda tertarik mengikuti program pelatihan kerja dari sekolah?',
                'opsi' => ['Sangat Tertarik', 'Tertarik', 'Cukup Tertarik', 'Kurang Tertarik', 'Tidak Tertarik'],
            ],
        ];

        foreach ($pertanyaan4 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_kuesioner' => $kuesioner4->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
            ]);

            foreach ($item['opsi'] as $opsi) {
                OpsiJawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'opsi' => $opsi,
                ]);
            }
        }

        // =============================================
        // KUESIONER 5: SURVEI KEPUASAN ALUMNI (UMUM)
        // =============================================
        $kuesioner5 = Kuesioner::create([
            'id_status' => 1, // Bekerja (bisa diisi semua status)
            'title' => 'Survei Kepuasan Alumni Secara Umum',
            'deskripsi' => 'Survei kepuasan alumni terhadap berbagai aspek pendidikan yang telah diterima, untuk semua status alumni.',
            'status' => 'aktif',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(12)->toDateString(),
            'tanggal_publikasi' => now()->toDateString(),
        ]);

        $pertanyaan5 = [
            [
                'isi_pertanyaan' => 'Bagaimana kualitas pengajaran dosen/guru di sekolah?',
                'opsi' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
            ],
            [
                'isi_pertanyaan' => 'Bagaimana fasilitas laboratorium/praktikum di sekolah?',
                'opsi' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
            ],
            [
                'isi_pertanyaan' => 'Apakah Anda merekomendasikan sekolah ini kepada orang lain?',
                'opsi' => ['Sangat Merekomendasikan', 'Merekomendasikan', 'Netral', 'Tidak Merekomendasikan', 'Sangat Tidak Merekomendasikan'],
            ],
            [
                'isi_pertanyaan' => 'Seberapa baik sekolah mempersiapkan Anda menghadapi dunia kerja/kuliah?',
                'opsi' => ['Sangat Baik', 'Baik', 'Cukup', 'Kurang', 'Sangat Kurang'],
            ],
            [
                'isi_pertanyaan' => 'Apakah Anda bersedia menjadi mentor untuk adik kelas/alumni baru?',
                'opsi' => ['Sangat Bersedia', 'Bersedia', 'Mungkin', 'Tidak Bersedia', 'Sangat Tidak Bersedia'],
            ],
        ];

        foreach ($pertanyaan5 as $item) {
            $pertanyaan = Pertanyaan::create([
                'id_kuesioner' => $kuesioner5->id_kuesioner,
                'isi_pertanyaan' => $item['isi_pertanyaan'],
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
