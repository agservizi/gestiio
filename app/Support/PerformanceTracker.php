<?php

namespace App\Support;

final class PerformanceTracker
{
    private static int $queryCount = 0;

    private static float $queryTimeMs = 0.0;

    public static function reset(): void
    {
        self::$queryCount = 0;
        self::$queryTimeMs = 0.0;
    }

    public static function recordQuery(float $timeMs): void
    {
        self::$queryCount++;
        self::$queryTimeMs += $timeMs;
    }

    public static function queryCount(): int
    {
        return self::$queryCount;
    }

    public static function queryTimeMs(): float
    {
        return round(self::$queryTimeMs, 2);
    }
}
