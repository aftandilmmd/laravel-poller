<?php

use Aftandilmmd\PollVote\Http\Controllers\Api\PollController;
use Aftandilmmd\PollVote\Http\Controllers\Api\PollOptionController;
use Aftandilmmd\PollVote\Http\Controllers\Api\PollVoteController;
use Illuminate\Support\Facades\Route;

$middleware = config('poll-vote.api.middleware', ['api', 'auth:sanctum']);

$rateLimit = config('poll-vote.api.rate_limit');
if ($rateLimit) {
    $middleware[] = 'throttle:'.$rateLimit.',1';
}

Route::group([
    'prefix' => config('poll-vote.api.prefix', 'api/polls'),
    'middleware' => $middleware,
], function () {
    // Poll CRUD
    Route::get('/', [PollController::class, 'index'])->name('poll-vote.api.polls.index');
    Route::post('/', [PollController::class, 'store'])->name('poll-vote.api.polls.store');
    Route::get('/{poll}', [PollController::class, 'show'])->name('poll-vote.api.polls.show');
    Route::put('/{poll}', [PollController::class, 'update'])->name('poll-vote.api.polls.update');
    Route::delete('/{poll}', [PollController::class, 'destroy'])->name('poll-vote.api.polls.destroy');

    // Poll Lifecycle
    Route::post('/{poll}/activate', [PollController::class, 'activate'])->name('poll-vote.api.polls.activate');
    Route::post('/{poll}/close', [PollController::class, 'close'])->name('poll-vote.api.polls.close');
    Route::post('/{poll}/cancel', [PollController::class, 'cancel'])->name('poll-vote.api.polls.cancel');
    Route::post('/{poll}/duplicate', [PollController::class, 'duplicate'])->name('poll-vote.api.polls.duplicate');

    // Poll Options
    Route::post('/{poll}/options', [PollOptionController::class, 'store'])->name('poll-vote.api.options.store');
    Route::put('/{poll}/options/{option}', [PollOptionController::class, 'update'])->name('poll-vote.api.options.update');
    Route::delete('/{poll}/options/{option}', [PollOptionController::class, 'destroy'])->name('poll-vote.api.options.destroy');
    Route::post('/{poll}/options/reorder', [PollOptionController::class, 'reorder'])->name('poll-vote.api.options.reorder');

    // Voting
    Route::post('/{poll}/vote', [PollVoteController::class, 'store'])->name('poll-vote.api.votes.store');
    Route::put('/{poll}/vote', [PollVoteController::class, 'update'])->name('poll-vote.api.votes.update');
    Route::delete('/{poll}/vote', [PollVoteController::class, 'destroy'])->name('poll-vote.api.votes.destroy');

    // Results
    Route::get('/{poll}/results', [PollController::class, 'results'])->name('poll-vote.api.polls.results');
    Route::get('/{poll}/votes', [PollVoteController::class, 'index'])->name('poll-vote.api.votes.index');
});
