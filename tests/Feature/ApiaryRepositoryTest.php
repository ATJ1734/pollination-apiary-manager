<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\ApiaryRepository;
use PHPUnit\Framework\TestCase;

final class ApiaryRepositoryTest extends TestCase
{
    public function test_create_find_update_delete_round_trip(): void
    {
        $repo = new ApiaryRepository();

        $id = $repo->create([
            'name' => 'TEST_Apiary',
            'location_description' => 'Test yard',
            'latitude' => null,
            'longitude' => null,
            'landowner_name' => null,
            'landowner_phone' => null,
            'notes' => null,
            'active' => 1,
        ]);

        $found = $repo->find($id);
        $this->assertSame('TEST_Apiary', $found['name']);

        $repo->update($id, ['name' => 'TEST_Apiary_Renamed']);
        $this->assertSame('TEST_Apiary_Renamed', $repo->find($id)['name']);

        $repo->delete($id);
        $this->assertNull($repo->find($id));
    }

    public function test_search_matches_name(): void
    {
        $repo = new ApiaryRepository();
        $id = $repo->create([
            'name' => 'TEST_SearchMe',
            'location_description' => null,
            'latitude' => null,
            'longitude' => null,
            'landowner_name' => null,
            'landowner_phone' => null,
            'notes' => null,
            'active' => 1,
        ]);

        $result = $repo->paginate(['q' => 'SearchMe'], 1, 25);

        $this->assertSame(1, $result['total']);
        $this->assertSame('TEST_SearchMe', $result['rows'][0]['name']);

        $repo->delete($id);
    }
}
