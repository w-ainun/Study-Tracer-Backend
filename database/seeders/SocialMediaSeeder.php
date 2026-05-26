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
            ['nama_sosmed' => 'Website', 'icon_sosmed' => 'globe'],
        ];

        foreach ($socialMediaList as $sosmed) {
            SocialMedia::firstOrCreate(
                ['nama_sosmed' => $sosmed['nama_sosmed']],
                ['icon_sosmed' => $sosmed['icon_sosmed']]
            );
        }
    }
}
