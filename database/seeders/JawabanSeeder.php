<?php

namespace Database\Seeders;

use App\Models\Jawaban;
use App\Models\Pertanyaan;
use App\Models\OpsiJawaban;
use App\Models\User;
use Illuminate\Database\Seeder;

class JawabanSeeder extends Seeder
{
    public function run(): void
    {
        $pertanyaans = Pertanyaan::with('opsiJawaban')->get();
        $alumniUsers = User::where('role', 'alumni')->pluck('id_users')->toArray();

        if ($pertanyaans->isEmpty() || empty($alumniUsers)) {
            $this->command->warn('Pertanyaan or Alumni users not found. Run KuesionerSeeder and UserSeeder first.');
            return;
        }

        // Select 30 random alumni to fill the questionnaire
        $selectedUsers = fake()->randomElements(
            $alumniUsers,
            min(30, count($alumniUsers))
        );

        foreach ($selectedUsers as $userId) {
            // Each alumni answers all published questions
            $publishedPertanyaans = $pertanyaans->where('status_pertanyaan', 'publish');

            foreach ($publishedPertanyaans as $pertanyaan) {
                $opsiJawabans = $pertanyaan->opsiJawaban;
                $hasOpsi = $opsiJawabans->isNotEmpty();

                Jawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'id_user' => $userId,
                    'id_opsiJawaban' => $hasOpsi ? $opsiJawabans->random()->id_opsi : null,
                    'jawaban' => $hasOpsi ? null : fake()->sentence(),
                    'status' => 'Selesai',
                ]);
            }
        }

        // Create some incomplete answers (Belum Selesai)
        $remainingUsers = array_diff($alumniUsers, $selectedUsers);
        $incompleteUsers = fake()->randomElements(
            $remainingUsers ?: $alumniUsers,
            min(10, count($remainingUsers ?: $alumniUsers))
        );

        foreach ($incompleteUsers as $userId) {
            // Answer only some questions partially
            $publishedPertanyaans = $pertanyaans->where('status_pertanyaan', 'publish');
            $partial = $publishedPertanyaans->random(
                min(fake()->numberBetween(1, 3), $publishedPertanyaans->count())
            );

            foreach ($partial as $pertanyaan) {
                $opsiJawabans = $pertanyaan->opsiJawaban;
                $hasOpsi = $opsiJawabans->isNotEmpty();

                Jawaban::create([
                    'id_pertanyaan' => $pertanyaan->id_pertanyaan,
                    'id_user' => $userId,
                    'id_opsiJawaban' => $hasOpsi ? $opsiJawabans->random()->id_opsi : null,
                    'jawaban' => $hasOpsi ? null : fake()->sentence(),
                    'status' => 'Belum Selesai',
                ]);
            }
        }

        $total = Jawaban::count();
        $this->command->info("Seeded {$total} jawaban records.");
    }
}
