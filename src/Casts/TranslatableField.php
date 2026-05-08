<?php

namespace Aftandilmmd\Poller\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class TranslatableField implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! self::enabled()) {
            return (string) $value;
        }

        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        if (! is_array($decoded)) {
            return (string) $value;
        }

        $locale = app()->getLocale();
        $fallback = config('poller.translatable.fallback_locale', 'en');

        return $decoded[$locale] ?? $decoded[$fallback] ?? (reset($decoded) ?: null);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! self::enabled()) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $existing = $attributes[$key] ?? null;
        $decoded = $existing ? json_decode((string) $existing, true) : [];

        if (! is_array($decoded)) {
            $decoded = [];
        }

        $decoded[app()->getLocale()] = (string) $value;

        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }

    public static function enabled(): bool
    {
        return (bool) config('poller.translatable.enabled', false);
    }
}
