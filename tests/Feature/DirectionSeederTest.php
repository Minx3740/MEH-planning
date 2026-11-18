<?php

namespace Tests\Feature;

use App\Models\Direction;
use App\Models\Group;
use Database\Seeders\DirectionSeeder;
use Tests\TestCase;

final class DirectionSeederTest extends TestCase
{
    public function test_it_creates_no_groups_or_directions_when_the_database_is_empty(): void
    {
        $this->seed(DirectionSeeder::class);

        $this->assertDatabaseCount('groups', 0);
        $this->assertDatabaseCount('directions', 0);
    }

    public function test_it_preserves_existing_directions_and_their_group_relations(): void
    {
        $firstGroup = Group::query()->create([
            'name' => 'Test group A',
            'description' => 'First test group',
        ]);
        $secondGroup = Group::query()->create([
            'name' => 'Test group B',
            'description' => 'Second test group',
        ]);

        $firstGroup->directions()->create([
            'name' => 'Test direction A',
            'code' => 'TSTA',
            'description' => 'First test direction',
        ]);
        $secondGroup->directions()->create([
            'name' => 'Test direction B',
            'code' => 'TSTB',
            'description' => 'Second test direction',
        ]);

        $expectedGroups = Group::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Group $group): array => $group->getAttributes())
            ->all();
        $expectedDirections = Direction::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Direction $direction): array => $direction->getAttributes())
            ->all();

        $this->seed(DirectionSeeder::class);

        $actualGroups = Group::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Group $group): array => $group->getAttributes())
            ->all();
        $actualDirections = Direction::query()
            ->with('group')
            ->orderBy('id')
            ->get();

        $this->assertSame($expectedGroups, $actualGroups);
        $this->assertSame(
            $expectedDirections,
            $actualDirections->map(static fn (Direction $direction): array => $direction->getAttributes())->all(),
        );
        $this->assertTrue($actualDirections[0]->group->is($firstGroup));
        $this->assertTrue($actualDirections[1]->group->is($secondGroup));
        $this->assertDatabaseCount('groups', 2);
        $this->assertDatabaseCount('directions', 2);
    }
}
