<?php

namespace Aftandilmmd\Poller\Enums;

enum PollType: string
{
    case YesNo = 'yes_no';
    case SingleChoice = 'single_choice';
    case MultipleChoice = 'multiple_choice';
    case Rating = 'rating';
    case Ranked = 'ranked';

    public function label(): string
    {
        return match ($this) {
            self::YesNo => __('poller::messages.type_yes_no'),
            self::SingleChoice => __('poller::messages.type_single_choice'),
            self::MultipleChoice => __('poller::messages.type_multiple_choice'),
            self::Rating => __('poller::messages.type_rating'),
            self::Ranked => __('poller::messages.type_ranked'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::YesNo => 'blue',
            self::SingleChoice => 'green',
            self::MultipleChoice => 'purple',
            self::Rating => 'amber',
            self::Ranked => 'indigo',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::YesNo => 'question-mark-circle',
            self::SingleChoice => 'radio',
            self::MultipleChoice => 'check-square',
            self::Rating => 'star',
            self::Ranked => 'sort-ascending',
        };
    }

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])->toArray();
    }

    public static function enabled(): array
    {
        return collect(self::cases())
            ->filter(fn ($case) => config("poller.types.{$case->value}", true))
            ->all();
    }
}
