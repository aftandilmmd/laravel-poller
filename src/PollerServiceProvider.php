<?php

namespace Aftandilmmd\Poller;

use Aftandilmmd\Poller\Commands\AutoClosePollsCommand;
use Aftandilmmd\Poller\Commands\AutoOpenPollsCommand;
use Aftandilmmd\Poller\Commands\ReconcileVoteCountsCommand;
use Aftandilmmd\Poller\Contracts\PollerServiceInterface;
use Aftandilmmd\Poller\Services\PollService;
use Illuminate\Support\ServiceProvider;

class PollerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/poller.php', 'poller');

        $this->app->singleton(PollerServiceInterface::class, function ($app) {
            return new PollService;
        });

        $this->app->alias(PollerServiceInterface::class, 'poller');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/poller.php' => config_path('poller.php'),
        ], 'poller-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'poller-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/poller'),
        ], 'poller-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => $this->app->langPath('vendor/poller'),
        ], 'poller-translations');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'poller');

        $viewsPath = __DIR__.'/../resources/views';
        if (is_dir($viewsPath)) {
            $this->loadViewsFrom($viewsPath, 'poller');
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
        if (! config('poller.livewire.enabled', true)) {
            return;
        }

        if (! class_exists(\Livewire\Livewire::class)) {
            return;
        }

        \Livewire\Livewire::component('poller-poll-manager', Livewire\PollManager::class);
        \Livewire\Livewire::component('poller-poll-form', Livewire\PollForm::class);
        \Livewire\Livewire::component('poller-poll-display', Livewire\PollDisplay::class);
        \Livewire\Livewire::component('poller-poll-results', Livewire\PollResults::class);
        \Livewire\Livewire::component('poller-poll-vote', Livewire\PollVote::class);
    }

    protected function registerApiRoutes(): void
    {
        if (! config('poller.api.enabled', false)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
