<?php

namespace Aftandilmmd\Poller\Database\Factories;

use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PollOption> */
class PollOptionFactory extends Factory
{
    protected $model = PollOption::class;

    public function definition(): array
    {
        return [
            'poll_id' => Poll::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 0,
        ];
    }

    public function withVotes(int $min = 5, int $max = 50): static
    {
        return $this->state(['votes_count' => fake()->numberBetween($min, $max)]);
    }

    public function yes(): static
    {
        return $this->state(['title' => 'Yes', 'sort_order' => 1]);
    }

    public function no(): static
    {
        return $this->state(['title' => 'No', 'sort_order' => 2]);
    }

    public function abstain(): static
    {
        return $this->state(['title' => 'Abstain', 'sort_order' => 3]);
    }

    public function custom(?int $createdBy = null): static
    {
        return $this->state([
            'is_custom' => true,
            'created_by' => $createdBy,
        ]);
    }
}
