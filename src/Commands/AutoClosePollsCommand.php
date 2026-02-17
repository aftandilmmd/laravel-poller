<?php

namespace Aftandilmmd\PollVote\Commands;

use Aftandilmmd\PollVote\Enums\PollStatus;
use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class AutoClosePollsCommand extends Command
{
    protected $signature = 'poll-vote:auto-close';

    protected $description = 'Close polls whose scheduled end time has passed';

    public function handle(): int
    {
        if (! config('poll-vote.features.auto_close', true)) {
            $this->info('Auto-close feature is disabled.');

            return self::SUCCESS;
        }

        $pollModel = config('poll-vote.models.poll', Poll::class);

        $polls = $pollModel::query()
            ->where('status', PollStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now())
            ->get();

        $service = App::make('poll-vote');
        $count = 0;

        foreach ($polls as $poll) {
            $service->close($poll);
            $count++;
        }

        $this->info("Closed {$count} poll(s).");

        return self::SUCCESS;
    }
}
