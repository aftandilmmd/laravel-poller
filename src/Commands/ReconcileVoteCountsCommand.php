<?php

namespace Aftandilmmd\PollVote\Commands;

use Aftandilmmd\PollVote\Models\PollOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileVoteCountsCommand extends Command
{
    protected $signature = 'poll-vote:reconcile-counts';

    protected $description = 'Recalculate votes_count on all poll options from actual vote records';

    public function handle(): int
    {
        $optionsTable = config('poll-vote.tables.options', 'poll_vote_poll_options');
        $votesTable = config('poll-vote.tables.votes', 'poll_vote_poll_votes');

        $updated = DB::statement("
            UPDATE {$optionsTable}
            SET votes_count = (
                SELECT COUNT(*)
                FROM {$votesTable}
                WHERE {$votesTable}.poll_option_id = {$optionsTable}.id
            )
        ");

        $count = PollOption::count();
        $this->info("Reconciled vote counts for {$count} option(s).");

        return self::SUCCESS;
    }
}
