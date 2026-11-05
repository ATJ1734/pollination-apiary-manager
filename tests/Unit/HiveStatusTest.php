<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\HiveStatus;
use PHPUnit\Framework\TestCase;

/** Brief section 6: "Dead, sold, and combined hives can't get new inspections, treatments, or placements." */
final class HiveStatusTest extends TestCase
{
    public function test_active_hive_can_receive_activity(): void
    {
        $this->assertTrue(HiveStatus::canReceiveActivity('active'));
    }

    public function test_queenless_hive_can_receive_activity(): void
    {
        $this->assertTrue(HiveStatus::canReceiveActivity('queenless'));
    }

    public function test_dead_hive_cannot_receive_activity(): void
    {
        $this->assertFalse(HiveStatus::canReceiveActivity('dead'));
    }

    public function test_sold_hive_cannot_receive_activity(): void
    {
        $this->assertFalse(HiveStatus::canReceiveActivity('sold'));
    }

    public function test_combined_hive_cannot_receive_activity(): void
    {
        $this->assertFalse(HiveStatus::canReceiveActivity('combined'));
    }
}
