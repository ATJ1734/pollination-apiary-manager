<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\ApiaryRepository;
use App\Repositories\ContractRepository;
use App\Repositories\GrowerRepository;
use App\Repositories\HiveRepository;
use App\Repositories\InspectionRepository;
use App\Repositories\PlacementRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/** Brief section 6: the overlap rule, the minimum-frames rule, and all-or-nothing assignment. */
final class PlacementBusinessRuleTest extends TestCase
{
    private int $apiaryId;
    private int $growerId;
    private int $contractId;
    private int $userId;

    protected function setUp(): void
    {
        $pdo = \App\Support\Database::connection();
        $pdo->prepare('INSERT INTO users (name, email, password_hash, role, active) VALUES (?, ?, ?, ?, 1)')
            ->execute(['TEST Inspector', 'test-inspector-' . bin2hex(random_bytes(6)) . '@example.com', 'x', 'field_tech']);
        $this->userId = (int) $pdo->lastInsertId();

        $this->apiaryId = (new ApiaryRepository())->create([
            'name' => 'TEST_Apiary_Placements', 'location_description' => null, 'latitude' => null,
            'longitude' => null, 'landowner_name' => null, 'landowner_phone' => null, 'notes' => null, 'active' => 1,
        ]);
        $this->growerId = (new GrowerRepository())->create([
            'farm_name' => 'TEST_Grower', 'contact_name' => null, 'phone' => null, 'email' => null,
            'address' => null, 'notes' => null,
        ]);
        $this->contractId = (new ContractRepository())->create([
            'grower_id' => $this->growerId, 'crop' => 'almonds', 'season_year' => 2026,
            'hives_required' => 10, 'min_frames_of_bees' => 8, 'rate_per_hive' => 200,
            'start_date' => '2026-12-01', 'end_date' => '2026-12-31', 'status' => 'signed',
        ]);
    }

    protected function tearDown(): void
    {
        $pdo = \App\Support\Database::connection();
        $pdo->prepare('DELETE FROM placements WHERE contract_id = ?')->execute([$this->contractId]);
        $pdo->prepare('DELETE FROM inspections WHERE hive_id IN (SELECT id FROM hives WHERE apiary_id = ?)')->execute([$this->apiaryId]);
        $pdo->prepare('DELETE FROM hives WHERE apiary_id = ?')->execute([$this->apiaryId]);
        (new ContractRepository())->delete($this->contractId);
        (new GrowerRepository())->delete($this->growerId);
        (new ApiaryRepository())->delete($this->apiaryId);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$this->userId]);
    }

    private function makeHive(string $code, int $frames): int
    {
        $hiveId = (new HiveRepository())->create([
            'hive_code' => $code, 'apiary_id' => $this->apiaryId, 'status' => 'active',
            'hive_type' => null, 'queen_installed_on' => null, 'queen_source' => null,
            'queen_mark_color' => null, 'established_on' => null, 'notes' => null,
        ]);
        (new InspectionRepository())->create([
            'hive_id' => $hiveId, 'inspected_on' => date('Y-m-d'), 'inspector_user_id' => $this->userId,
            'frames_of_bees' => $frames, 'brood_frames' => 4, 'queen_seen' => 1, 'eggs_seen' => 1,
            'temperament' => 2, 'honey_stores' => 3, 'mites_per_300' => 3,
        ]);

        return $hiveId;
    }

    public function test_overlapping_date_ranges_are_detected(): void
    {
        $hiveId = $this->makeHive('TEST-OVL-01', 10);
        $repo = new PlacementRepository();
        $repo->create(['contract_id' => $this->contractId, 'hive_id' => $hiveId, 'placed_on' => '2026-06-01', 'removed_on' => '2026-06-30', 'frames_at_placement' => 10]);

        // A new placement entirely inside the existing range overlaps.
        $this->assertTrue($repo->hasOverlap($hiveId, '2026-06-10', '2026-06-15'));
        // A new placement entirely after it does not.
        $this->assertFalse($repo->hasOverlap($hiveId, '2026-07-01', '2026-07-15'));
        // An open-ended (still-placed) existing placement overlaps anything from today onward.
    }

    public function test_assign_rejects_hive_below_minimum_frames(): void
    {
        $hiveId = $this->makeHive('TEST-MIN-01', 5); // contract requires 8

        $this->expectException(RuntimeException::class);
        (new PlacementRepository())->assignBulk($this->contractId, [$hiveId], date('Y-m-d'), 8);
    }

    public function test_assign_succeeds_when_all_hives_qualify(): void
    {
        $hiveA = $this->makeHive('TEST-OK-01', 10);
        $hiveB = $this->makeHive('TEST-OK-02', 9);

        $ids = (new PlacementRepository())->assignBulk($this->contractId, [$hiveA, $hiveB], date('Y-m-d'), 8);

        $this->assertCount(2, $ids);
        $this->assertSame(2, (new ContractRepository())->placedCount($this->contractId));
    }

    public function test_assign_is_all_or_nothing(): void
    {
        $goodHive = $this->makeHive('TEST_AON_GOOD', 10);
        $badHive = $this->makeHive('TEST_AON_BAD', 3); // below the minimum

        try {
            (new PlacementRepository())->assignBulk($this->contractId, [$goodHive, $badHive], date('Y-m-d'), 8);
            $this->fail('Expected assignBulk to throw.');
        } catch (RuntimeException) {
            // expected
        }

        // Neither hive should have been placed -- not even the good one.
        $this->assertSame(0, (new ContractRepository())->placedCount($this->contractId));
    }
}
