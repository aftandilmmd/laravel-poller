<?php

namespace Aftandilmmd\PollVote\Commands;

use Aftandilmmd\PollVote\Enums\PollStatus;
use Aftandilmmd\PollVote\Models\Poll;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class AutoOpenPollsCommand extends Command
{
    protected $signature = 'poll-vote:auto-open';

    protected $description = 'Activate polls whose scheduled start time has passed';

    public function handle(): int
    {
        if (! config('poll-vote.features.auto_open', true)) {
            $this->info('Auto-open feature is disabled.');

            return self::SUCCESS;
        }

        $pollModel = config('poll-vote.models.poll', Poll::class);

        $polls = $pollModel::query()
            ->where('status', PollStatus::Draft)
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', now())
            ->get();

        $service = App::make('poll-vote');
        $count = 0;

        foreach ($polls as $poll) {
            $service->activate($poll);
            $count++;
        }

        $this->info("Activated {$count} poll(s).");

        return self::SUCCESS;
    }
}
