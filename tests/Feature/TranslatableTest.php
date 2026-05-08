<?php

use Aftandilmmd\Poller\Models\Poll;
use Aftandilmmd\Poller\Models\PollOption;
use Illuminate\Foundation\Auth\User;

beforeEach(function () {
    $this->user = User::forceCreate([
        'name' => 'Translator',
        'email' => 'i18n@example.com',
        'password' => 'password',
    ]);

    config()->set('poller.translatable.enabled', true);
    config()->set('poller.translatable.fallback_locale', 'en');
});

it('stores title as JSON when translatable enabled', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => ['en' => 'Hello', 'tr' => 'Merhaba', 'az' => 'Salam'],
    ]);

    $raw = $poll->getRawOriginal('title');
    expect(json_decode($raw, true))->toBe(['en' => 'Hello', 'tr' => 'Merhaba', 'az' => 'Salam']);
});

it('returns title in current app locale', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => ['en' => 'Hello', 'tr' => 'Merhaba'],
    ]);

    app()->setLocale('tr');
    expect($poll->fresh()->title)->toBe('Merhaba');

    app()->setLocale('en');
    expect($poll->fresh()->title)->toBe('Hello');
});

it('falls back to fallback_locale when current locale missing', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => ['en' => 'Hello', 'tr' => 'Merhaba'],
    ]);

    app()->setLocale('de');
    expect($poll->fresh()->title)->toBe('Hello');
});

it('translate() returns specific locale value', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => ['en' => 'Hello', 'tr' => 'Merhaba', 'az' => 'Salam'],
    ]);

    expect($poll->translate('title', 'az'))->toBe('Salam');
    expect($poll->translate('title', 'tr'))->toBe('Merhaba');
});

it('setTranslation updates one locale without losing others', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => ['en' => 'Hello', 'tr' => 'Merhaba'],
    ]);

    $poll->setTranslation('title', 'tr', 'Selam')->save();

    $reloaded = $poll->fresh();
    expect($reloaded->translate('title', 'en'))->toBe('Hello');
    expect($reloaded->translate('title', 'tr'))->toBe('Selam');
});

it('getTranslations returns all locale variants', function () {
    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => ['en' => 'Hello', 'tr' => 'Merhaba'],
    ]);

    expect($poll->fresh()->getTranslations('title'))->toBe(['en' => 'Hello', 'tr' => 'Merhaba']);
});

it('treats title as plain string when translatable disabled', function () {
    config()->set('poller.translatable.enabled', false);

    $poll = Poll::factory()->create([
        'created_by' => $this->user->id,
        'title' => 'Plain title',
    ]);

    expect($poll->fresh()->title)->toBe('Plain title');
});

it('translatable works on PollOption too', function () {
    $poll = Poll::factory()->create(['created_by' => $this->user->id, 'title' => 'Poll']);

    $option = PollOption::factory()->for($poll)->create([
        'title' => ['en' => 'Option A', 'tr' => 'Seçenek A'],
    ]);

    app()->setLocale('tr');
    expect($option->fresh()->title)->toBe('Seçenek A');
});
