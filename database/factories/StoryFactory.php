<?php

namespace Database\Factories;

use App\Models\Story;
use App\Models\StoryStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Story>
 */
class StoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'narrative' => fake()->sentence(),
            'acceptance_criteria' => fake()->sentence(),
            'story_status_id' => StoryStatus::query()->where('key', 'draft')->value('id'),
            'story_type' => 'feature',
            'priority' => 0,
        ];
    }
}
