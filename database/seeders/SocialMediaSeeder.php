<?php

namespace Database\Seeders;

use App\Models\SocialMedia;
use Illuminate\Database\Seeder;

class SocialMediaSeeder extends Seeder
{
    public function run(): void
    {
        $socialMediaList = [
            ['nama_sosmed' => 'Instagram', 'icon_sosmed' => 'instagram'],
            ['nama_sosmed' => 'LinkedIn', 'icon_sosmed' => 'linkedin'],
            ['nama_sosmed' => 'Facebook', 'icon_sosmed' => 'facebook'],
            ['nama_sosmed' => 'Twitter/X', 'icon_sosmed' => 'twitter'],
            ['nama_sosmed' => 'GitHub', 'icon_sosmed' => 'github'],
            ['nama_sosmed' => 'YouTube', 'icon_sosmed' => 'youtube'],
            ['nama_sosmed' => 'TikTok', 'icon_sosmed' => 'tiktok'],
        ];

        foreach ($socialMediaList as $sosmed) {
            SocialMedia::create($sosmed);
        }
    }
}
