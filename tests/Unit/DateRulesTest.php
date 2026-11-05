<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validation\DateRules;
use PHPUnit\Framework\TestCase;

/** Brief section 6: "removed_on can't come before placed_on. remove_by can't come before applied_on." */
final class DateRulesTest extends TestCase
{
    public function test_null_date_is_allowed(): void
    {
        $this->assertTrue(DateRules::onOrAfter(null, '2026-01-01'));
    }

    public function test_later_date_passes(): void
    {
        $this->assertTrue(DateRules::onOrAfter('2026-02-01', '2026-01-01'));
    }

    public function test_same_date_passes(): void
    {
        $this->assertTrue(DateRules::onOrAfter('2026-01-01', '2026-01-01'));
    }

    public function test_earlier_date_fails(): void
    {
        $this->assertFalse(DateRules::onOrAfter('2025-12-31', '2026-01-01'));
    }
}
