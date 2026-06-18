<?php

namespace App\Support;

final class TenantContext
{
    private static ?int $schoolId = null;

    public static function set(int $schoolId): void
    {
        self::$schoolId = $schoolId;
    }

    public static function id(): ?int
    {
        return self::$schoolId;
    }

    public static function clear(): void
    {
        self::$schoolId = null;
    }
}
