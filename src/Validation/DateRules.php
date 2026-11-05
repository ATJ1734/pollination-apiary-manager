<?php

declare(strict_types=1);

namespace App\Validation;

/** Small date-ordering checks shared by treatments and placements (brief section 6). */
final class DateRules
{
    /** True if $later is on or after $earlier (both "Y-m-d" strings), or $later is null. */
    public static function onOrAfter(?string $later, string $earlier): bool
    {
        return $later === null || $later >= $earlier;
    }
}
