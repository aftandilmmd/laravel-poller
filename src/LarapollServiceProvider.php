<?php

namespace Aftandilmmd\Larapoll;

use Aftandilmmd\Larapoll\Commands\AutoClosePollsCommand;
use Aftandilmmd\Larapoll\Commands\AutoOpenPollsCommand;
use Aftandilmmd\Larapoll\Commands\ReconcileVoteCountsCommand;
use Aftandilmmd\Larapoll\Contracts\LarapollServiceInterface;
use Aftandilmmd\Larapoll\Services\PollService;
use Illuminate\Support\ServiceProvider;

class LarapollServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/larapoll.php', 'larapoll');

        $this->app->singleton(LarapollServiceInterface::class, function ($app) {
            return new PollService;
        });

        $this->app->alias(LarapollServiceInterface::class, 'larapoll');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/larapoll.php' => config_path('larapoll.php'),
        ], 'larapoll-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'larapoll-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/larapoll'),
        ], 'larapoll-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/larapoll'),
        ], 'larapoll-translations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'larapoll');

        $viewsPath = __DIR__.'/../resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'larapoll');
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
        if (! config('larapoll.livewire.enabled', true)) {
            return;
        }

        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        \Livewire\Livewire::component('larapoll-poll-manager', Livewire\PollManager::class);
        \Livewire\Livewire::component('larapoll-poll-form', Livewire\PollForm::class);
        \Livewire\Livewire::component('larapoll-poll-display', Livewire\PollDisplay::class);
        \Livewire\Livewire::component('larapoll-poll-results', Livewire\PollResults::class);
        \Livewire\Livewire::component('larapoll-poll-vote', Livewire\PollVote::class);
    }

    protected function registerApiRoutes(): void
    {
        if (! config('larapoll.api.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
