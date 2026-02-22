<?php

namespace Database\Factories;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class SkillFactory extends Factory
{
    protected $model = Skill::class;

    public function definition(): array
    {
        $skills = [
            'PHP', 'JavaScript', 'Python', 'Java', 'HTML/CSS',
            'Laravel', 'React', 'Vue.js', 'Node.js', 'MySQL',
            'Microsoft Office', 'Adobe Photoshop', 'AutoCAD',
            'Public Speaking', 'Project Management', 'Data Analysis',
        ];

        return [
            'name_skills' => fake()->randomElement($skills),
        ];
    }
}
