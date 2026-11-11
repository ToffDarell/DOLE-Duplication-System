<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::query()->inRandomOrder()->value('id') ?? 1,
            'name' => fake()->words(3, true).' Project',
            'implementation_type' => 'Direct Implementation',
            'description' => fake()->sentence(),
            'municipality' => 'Pangantucan',
            'target_beneficiaries' => 500,
            'year' => (int) date('Y'),
        ];
    }
}
