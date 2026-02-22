<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    public function run(): void
    {
        $statusList = [
            'Bekerja',
            'Kuliah',
            'Wirausaha',
            'Belum Bekerja',
        ];

        foreach ($statusList as $status) {
            Status::create(['nama_status' => $status]);
        }
    }
}
