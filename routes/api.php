<?php

use Aftandilmmd\Larapoll\Http\Controllers\Api\PollController;
use Aftandilmmd\Larapoll\Http\Controllers\Api\PollOptionController;
use Aftandilmmd\Larapoll\Http\Controllers\Api\PollVoteController;
use Illuminate\Support\Facades\Route;

$middleware = config('larapoll.api.middleware', ['api', 'auth:sanctum']);

$rateLimit = config('larapoll.api.rate_limit');
if ($rateLimit) {
    $middleware[] = 'throttle:'.$rateLimit.',1';
}

Route::group([
    'prefix' => config('larapoll.api.prefix', 'api/polls'),
    'middleware' => $middleware,
], function () {
    // Poll CRUD
    Route::get('/', [PollController::class, 'index'])->name('larapoll.api.polls.index');
    Route::post('/', [PollController::class, 'store'])->name('larapoll.api.polls.store');
    Route::get('/{poll}', [PollController::class, 'show'])->name('larapoll.api.polls.show');
    Route::put('/{poll}', [PollController::class, 'update'])->name('larapoll.api.polls.update');
    Route::delete('/{poll}', [PollController::class, 'destroy'])->name('larapoll.api.polls.destroy');

    // Poll Lifecycle
    Route::post('/{poll}/activate', [PollController::class, 'activate'])->name('larapoll.api.polls.activate');
    Route::post('/{poll}/close', [PollController::class, 'close'])->name('larapoll.api.polls.close');
    Route::post('/{poll}/cancel', [PollController::class, 'cancel'])->name('larapoll.api.polls.cancel');
    Route::post('/{poll}/duplicate', [PollController::class, 'duplicate'])->name('larapoll.api.polls.duplicate');

    // Poll Options
    Route::post('/{poll}/options', [PollOptionController::class, 'store'])->name('larapoll.api.options.store');
    Route::put('/{poll}/options/{option}', [PollOptionController::class, 'update'])->name('larapoll.api.options.update');
    Route::delete('/{poll}/options/{option}', [PollOptionController::class, 'destroy'])->name('larapoll.api.options.destroy');
    Route::post('/{poll}/options/reorder', [PollOptionController::class, 'reorder'])->name('larapoll.api.options.reorder');

    // Voting
    Route::post('/{poll}/vote', [PollVoteController::class, 'store'])->name('larapoll.api.votes.store');
    Route::put('/{poll}/vote', [PollVoteController::class, 'update'])->name('larapoll.api.votes.update');
    Route::delete('/{poll}/vote', [PollVoteController::class, 'destroy'])->name('larapoll.api.votes.destroy');

    // Results
    Route::get('/{poll}/results', [PollController::class, 'results'])->name('larapoll.api.polls.results');
    Route::get('/{poll}/votes', [PollVoteController::class, 'index'])->name('larapoll.api.votes.index');
});
