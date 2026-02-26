<?php

namespace Database\Factories;

use App\Models\Alumni;
use App\Models\Jurusan;
use App\Models\User;
use App\Models\Kota;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniFactory extends Factory
{
    protected $model = Alumni::class;

    public function definition(): array
    {
        $kota = Kota::inRandomOrder()->first();
        $kotaName = $kota ? $kota->nama_kota : fake()->city();

        return [
            'nama_alumni' => fake()->name(),
            'nis' => fake()->numerify('######'),
            'nisn' => fake()->numerify('##########'),
            'jenis_kelamin' => fake()->randomElement(['Laki-laki', 'Perempuan']),
            'tanggal_lahir' => fake()->date('Y-m-d', '2005-12-31'),
            'tempat_lahir' => $kotaName,
            'tahun_masuk' => fake()->year(),
            'foto' => null,
            'alamat' => fake()->streetAddress() . ', ' . $kotaName,
            'no_hp' => fake()->phoneNumber(),
            'id_jurusan' => Jurusan::factory(),
            'tahun_lulus' => fake()->date('Y-m-d'),
            'id_users' => User::factory(),
            'status_create' => fake()->randomElement(['pending', 'ok', 'rejected']),
        ];
    }
}
