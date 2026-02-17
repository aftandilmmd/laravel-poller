<?php

namespace Aftandilmmd\PollVote\Database\Factories;

use Aftandilmmd\PollVote\Enums\PollStatus;
use Aftandilmmd\PollVote\Enums\PollType;
use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Poll> */
class PollFactory extends Factory
{
    protected $model = Poll::class;

    public function definition(): array
    {
        return [
            'created_by' => fn () => \Illuminate\Foundation\Auth\User::forceCreate([
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password',
            ])->id,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'type' => PollType::SingleChoice,
            'status' => PollStatus::Draft,
            'is_anonymous' => false,
            'show_results_before_close' => false,
            'allow_vote_change' => false,
            'requires_comment' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => PollStatus::Draft]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => PollStatus::Active,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDays(7),
        ]);
    }

    public function closed(): static
    {
        return $this->state([
            'status' => PollStatus::Closed,
            'starts_at' => now()->subWeek(),
            'ends_at' => now()->subDay(),
            'closed_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => PollStatus::Cancelled]);
    }

    public function yesNo(): static
    {
        return $this->state([
            'type' => PollType::YesNo,
            'min_selections' => 1,
            'max_selections' => 1,
        ]);
    }

    public function singleChoice(): static
    {
        return $this->state(['type' => PollType::SingleChoice]);
    }

    public function multipleChoice(): static
    {
        return $this->state([
            'type' => PollType::MultipleChoice,
            'min_selections' => 1,
            'max_selections' => 3,
        ]);
    }

    public function rating(): static
    {
        return $this->state(['type' => PollType::Rating]);
    }

    public function ranked(): static
    {
        return $this->state(['type' => PollType::Ranked]);
    }

    public function anonymous(): static
    {
        return $this->state(['is_anonymous' => true]);
    }

    public function withVoteChange(): static
    {
        return $this->state(['allow_vote_change' => true]);
    }

    public function withComment(): static
    {
        return $this->state(['requires_comment' => true]);
    }

    public function liveResults(): static
    {
        return $this->state(['show_results_before_close' => true]);
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => PollStatus::Draft,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addWeek(),
        ]);
    }

    public function withCustomOptions(?int $max = null): static
    {
        return $this->state([
            'allow_custom_options' => true,
            'max_custom_options' => $max,
        ]);
    }
}
