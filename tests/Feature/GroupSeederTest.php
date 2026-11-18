<?php

namespace Tests\Feature;

use App\Models\Group;
use Database\Seeders\GroupSeeder;
use Tests\TestCase;

final class GroupSeederTest extends TestCase
{
    public function test_it_creates_no_groups_when_the_database_is_empty(): void
    {
        $this->seed(GroupSeeder::class);

        $this->assertDatabaseCount('groups', 0);
    }

    public function test_it_preserves_all_existing_group_attributes(): void
    {
        Group::query()->create([
            'name' => 'Test group A',
            'description' => 'First test group',
        ]);
        Group::query()->create([
            'name' => 'Test group B',
            'description' => 'Second test group',
        ]);

        $expectedGroups = Group::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Group $group): array => $group->getAttributes())
            ->all();

        $this->seed(GroupSeeder::class);

        $actualGroups = Group::query()
            ->orderBy('id')
            ->get()
            ->map(static fn (Group $group): array => $group->getAttributes())
            ->all();

        $this->assertSame($expectedGroups, $actualGroups);
        $this->assertDatabaseCount('groups', 2);
    }
}
