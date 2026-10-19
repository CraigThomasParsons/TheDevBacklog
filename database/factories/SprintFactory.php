<?php

namespace Database\Factories;

use App\Models\Sprint;
use App\Models\SprintStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sprint>
 */
class SprintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'goal' => fake()->sentence(),
            'success_criteria' => fake()->sentence(),
            'sprint_status_id' => SprintStatus::query()->where('key', 'draft')->value('id'),
            'is_frozen' => false,
            'frozen_at' => null,
        ];
    }
}
