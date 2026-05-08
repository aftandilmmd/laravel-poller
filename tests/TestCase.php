<?php

namespace Aftandilmmd\Poller\Tests;

use Aftandilmmd\Poller\PollerServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('poller.user_model', \Illuminate\Foundation\Auth\User::class);
    }

    protected function getPackageProviders($app): array
    {
        $providers = [PollerServiceProvider::class];

        if (class_exists(\Livewire\LivewireServiceProvider::class)) {
            array_unshift($providers, \Livewire\LivewireServiceProvider::class);
        }

        return $providers;
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom([
            __DIR__.'/database/migrations',
            __DIR__.'/../database/migrations',
        ]);
    }
}
