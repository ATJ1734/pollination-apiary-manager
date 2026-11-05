<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\ApiaryRepository;
use App\Repositories\HiveRepository;
use App\Repositories\InspectionRepository;
use App\Support\HiveStatus;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end version of HiveStatusTest: confirms a retired hive really is
 * excluded from the list InspectionController offers, not just that the
 * pure helper function says so.
 */
final class InspectionBusinessRuleTest extends TestCase
{
    public function test_dead_hive_is_excluded_from_inspectable_hives(): void
    {
        $apiaryId = (new ApiaryRepository())->create([
            'name' => 'TEST_Apiary_ForDeadHive',
            'location_description' => null, 'latitude' => null, 'longitude' => null,
            'landowner_name' => null, 'landowner_phone' => null, 'notes' => null, 'active' => 1,
        ]);

        $hiveRepo = new HiveRepository();
        $hiveId = $hiveRepo->create([
            'hive_code' => 'TEST-DEAD-01',
            'apiary_id' => $apiaryId,
            'status' => 'dead',
            'hive_type' => null, 'queen_installed_on' => null, 'queen_source' => null,
            'queen_mark_color' => null, 'established_on' => null, 'notes' => null,
        ]);

        $hive = $hiveRepo->find($hiveId);
        $this->assertFalse(HiveStatus::canReceiveActivity($hive['status']));

        $inspectable = (new InspectionRepository())->inspectableHives();
        $ids = array_column($inspectable, 'id');
        $this->assertNotContains($hiveId, $ids);

        $hiveRepo->delete($hiveId);
        (new ApiaryRepository())->delete($apiaryId);
    }

    public function test_active_hive_is_included_in_inspectable_hives(): void
    {
        $apiaryId = (new ApiaryRepository())->create([
            'name' => 'TEST_Apiary_ForActiveHive',
            'location_description' => null, 'latitude' => null, 'longitude' => null,
            'landowner_name' => null, 'landowner_phone' => null, 'notes' => null, 'active' => 1,
        ]);

        $hiveRepo = new HiveRepository();
        $hiveId = $hiveRepo->create([
            'hive_code' => 'TEST-ACTIVE-01',
            'apiary_id' => $apiaryId,
            'status' => 'active',
            'hive_type' => null, 'queen_installed_on' => null, 'queen_source' => null,
            'queen_mark_color' => null, 'established_on' => null, 'notes' => null,
        ]);

        $inspectable = (new InspectionRepository())->inspectableHives();
        $ids = array_column($inspectable, 'id');
        $this->assertContains($hiveId, $ids);

        $hiveRepo->delete($hiveId);
        (new ApiaryRepository())->delete($apiaryId);
    }
}
