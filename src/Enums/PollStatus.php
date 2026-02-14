<?php

namespace Aftandilmmd\Larapoll\Enums;

enum PollStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('larapoll::messages.status_draft'),
            self::Active => __('larapoll::messages.status_active'),
            self::Closed => __('larapoll::messages.status_closed'),
            self::Cancelled => __('larapoll::messages.status_cancelled'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'green',
            self::Closed => 'blue',
            self::Cancelled => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil',
            self::Active => 'play',
            self::Closed => 'lock-closed',
            self::Cancelled => 'x-circle',
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
}
