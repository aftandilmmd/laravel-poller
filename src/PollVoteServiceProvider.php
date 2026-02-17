<?php

namespace Aftandilmmd\PollVote;

use Aftandilmmd\PollVote\Commands\AutoClosePollsCommand;
use Aftandilmmd\PollVote\Commands\AutoOpenPollsCommand;
use Aftandilmmd\PollVote\Commands\ReconcileVoteCountsCommand;
use Aftandilmmd\PollVote\Contracts\PollVoteServiceInterface;
use Aftandilmmd\PollVote\Services\PollService;
use Illuminate\Support\ServiceProvider;

class PollVoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/poll-vote.php', 'poll-vote');

        $this->app->singleton(PollVoteServiceInterface::class, function ($app) {
            return new PollService;
        });

        $this->app->alias(PollVoteServiceInterface::class, 'poll-vote');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/poll-vote.php' => config_path('poll-vote.php'),
        ], 'poll-vote-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'poll-vote-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/poll-vote'),
        ], 'poll-vote-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/poll-vote'),
        ], 'poll-vote-translations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'poll-vote');

        $viewsPath = __DIR__.'/../resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'poll-vote');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                AutoOpenPollsCommand::class,
                AutoClosePollsCommand::class,
                ReconcileVoteCountsCommand::class,
            ]);
        }

        $this->registerLivewireComponents();
        $this->registerApiRoutes();
    }

    protected function registerLivewireComponents(): void
    {
        if (! config('poll-vote.livewire.enabled', true)) {
            return;
        }

        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        \Livewire\Livewire::component('poll-vote-poll-manager', Livewire\PollManager::class);
        \Livewire\Livewire::component('poll-vote-poll-form', Livewire\PollForm::class);
        \Livewire\Livewire::component('poll-vote-poll-display', Livewire\PollDisplay::class);
        \Livewire\Livewire::component('poll-vote-poll-results', Livewire\PollResults::class);
        \Livewire\Livewire::component('poll-vote-poll-vote', Livewire\PollVote::class);
    }

    protected function registerApiRoutes(): void
    {
        if (! config('poll-vote.api.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
