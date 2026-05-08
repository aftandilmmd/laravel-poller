<?php

use Aftandilmmd\Poller\Http\Controllers\Api\PollController;
use Aftandilmmd\Poller\Http\Controllers\Api\PollOptionController;
use Aftandilmmd\Poller\Http\Controllers\Api\PollVoteController;
use Illuminate\Support\Facades\Route;

$middleware = config('poller.api.middleware', ['api', 'auth:sanctum']);

$rateLimit = config('poller.api.rate_limit');
if ($rateLimit) {
    $middleware[] = 'throttle:'.$rateLimit.',1';
}

Route::group([
    'prefix' => config('poller.api.prefix', 'api/polls'),
    'middleware' => $middleware,
], function () {
    // Poll CRUD
    Route::get('/', [PollController::class, 'index'])->name('poller.api.polls.index');
    Route::post('/', [PollController::class, 'store'])->name('poller.api.polls.store');
    Route::get('/{poll}', [PollController::class, 'show'])->name('poller.api.polls.show');
    Route::put('/{poll}', [PollController::class, 'update'])->name('poller.api.polls.update');
    Route::delete('/{poll}', [PollController::class, 'destroy'])->name('poller.api.polls.destroy');

    // Poll Lifecycle
    Route::post('/{poll}/activate', [PollController::class, 'activate'])->name('poller.api.polls.activate');
    Route::post('/{poll}/close', [PollController::class, 'close'])->name('poller.api.polls.close');
    Route::post('/{poll}/cancel', [PollController::class, 'cancel'])->name('poller.api.polls.cancel');
    Route::post('/{poll}/duplicate', [PollController::class, 'duplicate'])->name('poller.api.polls.duplicate');

    // Poll Options
    Route::post('/{poll}/options', [PollOptionController::class, 'store'])->name('poller.api.options.store');
    Route::put('/{poll}/options/{option}', [PollOptionController::class, 'update'])->name('poller.api.options.update');
    Route::delete('/{poll}/options/{option}', [PollOptionController::class, 'destroy'])->name('poller.api.options.destroy');
    Route::post('/{poll}/options/reorder', [PollOptionController::class, 'reorder'])->name('poller.api.options.reorder');

    // Voting
    Route::post('/{poll}/vote', [PollVoteController::class, 'store'])->name('poller.api.votes.store');
    Route::put('/{poll}/vote', [PollVoteController::class, 'update'])->name('poller.api.votes.update');
    Route::delete('/{poll}/vote', [PollVoteController::class, 'destroy'])->name('poller.api.votes.destroy');

    // Results
    Route::get('/{poll}/results', [PollController::class, 'results'])->name('poller.api.polls.results');
    Route::get('/{poll}/votes', [PollVoteController::class, 'index'])->name('poller.api.votes.index');
});
