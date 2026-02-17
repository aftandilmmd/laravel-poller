<?php

namespace Aftandilmmd\PollVote\Database\Factories;

use Aftandilmmd\PollVote\Models\Poll;
use Aftandilmmd\PollVote\Models\PollOption;
use Aftandilmmd\PollVote\Models\PollVote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PollVote> */
class PollVoteFactory extends Factory
{
    protected $model = PollVote::class;

    public function definition(): array
    {
        return [
            'poll_id' => Poll::factory(),
            'poll_option_id' => PollOption::factory(),
            'user_id' => config('poll-vote.user_model', \App\Models\User::class)::factory(),
        ];
    }

    public function withComment(): static
    {
        return $this->state(['comment' => fake()->sentence()]);
    }

    public function withRank(int $rank): static
    {
        return $this->state(['rank' => $rank]);
    }

    public function withRating(int $rating): static
    {
        return $this->state(['rating' => $rating]);
    }
}
