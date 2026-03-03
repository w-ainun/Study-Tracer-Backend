<?php

namespace Database\Seeders;

use App\Models\Lowongan;
use App\Models\Perusahaan;
use Illuminate\Database\Seeder;

class LowonganSeeder extends Seeder
{
    public function run(): void
    {
        $perusahaanIds = Perusahaan::pluck('id_perusahaan')->toArray();

        if (empty($perusahaanIds)) {
            return;
        }

        $lowonganData = [
            ['judul' => 'Web Developer', 'deskripsi' => 'Dibutuhkan Web Developer dengan pengalaman minimal 1 tahun.'],
            ['judul' => 'Staff Administrasi', 'deskripsi' => 'Membuka lowongan untuk posisi Staff Administrasi.'],
            ['judul' => 'Marketing Online', 'deskripsi' => 'Dicari Marketing Online yang kreatif dan inovatif.'],
            ['judul' => 'Graphic Designer', 'deskripsi' => 'Membutuhkan Graphic Designer untuk tim kreatif.'],
            ['judul' => 'Network Engineer', 'deskripsi' => 'Lowongan Network Engineer untuk perusahaan IT.'],
            ['judul' => 'Data Entry Operator', 'deskripsi' => 'Dibutuhkan Data Entry Operator yang teliti.'],
            ['judul' => 'Customer Service', 'deskripsi' => 'Membuka lowongan Customer Service berpengalaman.'],
            ['judul' => 'Teknisi Komputer', 'deskripsi' => 'Dicari Teknisi Komputer untuk maintenance hardware.'],
        ];

        foreach ($lowonganData as $data) {
            Lowongan::create([
                'judul_lowongan' => $data['judul'],
                'deskripsi' => $data['deskripsi'],
                
                // Field baru dari migrasi enhance_lowongan_table
                'tipe_pekerjaan' => fake()->randomElement(['Full-time', 'Part-time', 'Internship', 'Contract']),
                'lokasi' => fake()->city(),
                
                'status' => fake()->randomElement(['draft', 'published', 'closed']),
                'approval_status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                
                // Diubah dari time() menjadi date() karena perubahan tipe kolom
                'lowongan_selesai' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'), 
                
                // Field baru dari migrasi create_lowongan_skills_table_and_add_time_columns
                'jam_mulai' => '08:00:00',
                'jam_berakhir' => '17:00:00',
                
                'id_pekerjaan' => null,
                'foto_lowongan' => null,
                'id_perusahaan' => fake()->randomElement($perusahaanIds),
                
                // Field baru (nullable)
                'id_users' => null,
            ]);
        }
    }
}