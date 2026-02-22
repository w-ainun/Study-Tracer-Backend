<?php

namespace Database\Factories;

use App\Models\AlumniSkill;
use App\Models\Alumni;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlumniSkillFactory extends Factory
{
    protected $model = AlumniSkill::class;

    public function definition(): array
    {
        return [
            'id_alumni' => Alumni::factory(),
            'id_skills' => Skill::factory(),
        ];
    }
}
