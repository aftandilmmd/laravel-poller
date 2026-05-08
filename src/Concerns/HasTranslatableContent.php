<?php

namespace Aftandilmmd\Poller\Concerns;

use Aftandilmmd\Poller\Casts\TranslatableField;

trait HasTranslatableContent
{
    public function translate(string $key, string $locale): ?string
    {
        $raw = $this->getRawOriginal($key);

        if ($raw === null) {
            return null;
        }

        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return (string) $raw;
        }

        return $decoded[$locale] ?? $decoded[config('poller.translatable.fallback_locale', 'en')] ?? null;
    }

    public function setTranslation(string $key, string $locale, ?string $value): self
    {
        $raw = $this->getRawOriginal($key);
        $decoded = $raw ? json_decode((string) $raw, true) : [];

        if (! is_array($decoded)) {
            $decoded = [];
        }

        if ($value === null) {
            unset($decoded[$locale]);
        } else {
            $decoded[$locale] = $value;
        }

        $this->attributes[$key] = json_encode($decoded, JSON_UNESCAPED_UNICODE);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getTranslations(string $key): array
    {
        $raw = $this->getRawOriginal($key);

        if ($raw === null) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function translatableCasts(array $existing, array $fields): array
    {
        if (! TranslatableField::enabled()) {
            return $existing;
        }

        foreach ($fields as $field) {
            $existing[$field] = TranslatableField::class;
        }

        return $existing;
    }
}
