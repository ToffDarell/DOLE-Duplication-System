<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $programs = [
            ['code' => 'TUPAD', 'name' => 'Tulong Panghanapbuhay sa Ating Disadvantaged/Displaced Workers'],
            ['code' => 'SPES', 'name' => 'Special Program for Employment of Students'],
            ['code' => 'DILP', 'name' => 'DOLE Integrated Livelihood Program'],
            ['code' => 'GIP', 'name' => 'Government Internship Program'],
        ];

        foreach ($programs as $program) {
            Program::firstOrCreate(['code' => $program['code']], $program);
        }
    }
}
