<?php

declare(strict_types=1);

namespace App\Support;

/** The one place that knows which hive statuses are "retired" (brief section 6). */
final class HiveStatus
{
    public const RETIRED = ['dead', 'sold', 'combined'];

    public static function canReceiveActivity(string $status): bool
    {
        return !in_array($status, self::RETIRED, true);
    }
}
