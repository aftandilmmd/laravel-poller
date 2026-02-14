<?php

namespace Aftandilmmd\Larapoll\Commands;

use Aftandilmmd\Larapoll\Models\PollOption;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileVoteCountsCommand extends Command
{
    protected $signature = 'larapoll:reconcile-counts';

    protected $description = 'Recalculate votes_count on all poll options from actual vote records';

    public function handle(): int
    {
        $optionsTable = config('larapoll.tables.options', 'larapoll_poll_options');
        $votesTable = config('larapoll.tables.votes', 'larapoll_poll_votes');

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
