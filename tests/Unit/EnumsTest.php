<?php

use Aftandilmmd\Larapoll\Enums\PollStatus;
use Aftandilmmd\Larapoll\Enums\PollType;

it('has correct poll type values', function () {
    expect(PollType::values())->toBe([
        'yes_no',
        'single_choice',
        'multiple_choice',
        'rating',
        'ranked',
    ]);
});

it('has correct poll status values', function () {
    expect(PollStatus::values())->toBe([
        'draft',
        'active',
        'closed',
        'cancelled',
    ]);
});

it('returns labels for poll types', function () {
    expect(PollType::SingleChoice->label())->toBe(__('larapoll::messages.type_single_choice'));
    expect(PollType::YesNo->label())->toBe(__('larapoll::messages.type_yes_no'));
    expect(PollType::Rating->label())->toBe(__('larapoll::messages.type_rating'));
});

it('returns colors for poll statuses', function () {
    expect(PollStatus::Active->color())->toBe('green');
    expect(PollStatus::Draft->color())->toBe('gray');
    expect(PollStatus::Closed->color())->toBe('blue');
    expect(PollStatus::Cancelled->color())->toBe('red');
});

it('returns options for select dropdowns', function () {
    $options = PollType::options();

    expect($options)->toBeArray()
        ->toHaveCount(5)
        ->toHaveKey('single_choice')
        ->toHaveKey('yes_no');
});

it('returns enabled poll types from config', function () {
    config()->set('larapoll.types.rating', false);

    $enabled = PollType::enabled();

    expect($enabled)->toHaveCount(4);
    expect(collect($enabled)->pluck('value')->toArray())->not->toContain('rating');
});
