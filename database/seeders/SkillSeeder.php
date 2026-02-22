<?php

namespace Database\Seeders;

use App\Models\Skill;
use Illuminate\Database\Seeder;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'PHP', 'JavaScript', 'Python', 'Java', 'HTML/CSS',
            'Laravel', 'React', 'Vue.js', 'Node.js', 'MySQL',
            'Microsoft Office', 'Adobe Photoshop', 'Adobe Illustrator',
            'AutoCAD', 'Public Speaking', 'Project Management',
            'Data Analysis', 'Networking', 'Cyber Security', 'UI/UX Design',
        ];

        foreach ($skills as $skill) {
            Skill::create(['name_skills' => $skill]);
        }
    }
}
