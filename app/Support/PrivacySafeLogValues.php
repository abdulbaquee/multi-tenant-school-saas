<?php

namespace App\Support;

final class PrivacySafeLogValues
{
    private const PRIVATE_KEY_PARTS = [
        'password',
        'token',
        'secret',
        'credential',
        'session',
        'payload',
        'guardian',
        'parent_',
        'address',
        'date_of_birth',
        'dob',
        'photo',
    ];

    /**
     * @param  array<string, mixed>|null  $values
     * @return array<string, mixed>
     */
    public static function sanitize(?array $values): array
    {
        if ($values === null) {
            return [];
        }

        return collect($values)
            ->reject(fn (mixed $value, string|int $key): bool => self::isPrivateKey((string) $key))
            ->map(fn (mixed $value): mixed => is_array($value) ? self::sanitize($value) : $value)
            ->all();
    }

    private static function isPrivateKey(string $key): bool
    {
        $normalizedKey = strtolower($key);

        return collect(self::PRIVATE_KEY_PARTS)
            ->contains(fn (string $part): bool => str_contains($normalizedKey, $part));
    }
}
