<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Direction;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class AuthorizationAccessTest extends TestCase
{
    public function test_role_and_direction_checks_block_cross_direction_access(): void
    {
        $group = Group::query()->firstOrCreate(['name' => 'Group A']);
        $directionA = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Direction A',
        ]);
        $directionB = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Direction B',
        ]);

        $admin = $this->makeUser('ADMIN', $directionA, 'admin@example.test');
        $sg = $this->makeUser('SG', null, 'sg@example.test');
        $directorA = $this->makeUser('DIRECTEUR', $directionA, 'director-a@example.test');
        $directorB = $this->makeUser('DIRECTEUR', $directionB, 'director-b@example.test');

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($directorA->isAdmin());
        $this->assertTrue($sg->isSg());
        $this->assertTrue($directorA->isDirector());

        $this->assertTrue(Gate::forUser($admin)->allows('admin'));
        $this->assertFalse(Gate::forUser($directorA)->allows('admin'));
        $this->assertFalse(Gate::forUser($directorA)->allows('sg'));

        $this->assertTrue($sg->canAccessDirection($directionA));
        $this->assertTrue($sg->canAccessDirection($directionB));
        $this->assertFalse($directorA->canAccessDirection($directionB));
        $this->assertFalse($directorB->canAccessDirection($directionA));

        $this->assertTrue($directorA->canManageDirection($directionA));
        $this->assertFalse($directorA->canManageDirection($directionB));
        $this->assertFalse($sg->canManageDirection($directionA));
    }

    public function test_activity_and_direction_policies_enforce_server_side_direction_isolation(): void
    {
        $group = Group::query()->firstOrCreate(['name' => 'Group B']);
        $directionA = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Direction C',
        ]);
        $directionB = Direction::query()->firstOrCreate([
            'group_id' => $group->id,
            'name' => 'Direction D',
        ]);

        $directorA = $this->makeUser('DIRECTEUR', $directionA, 'director-c@example.test');
        $directorB = $this->makeUser('DIRECTEUR', $directionB, 'director-d@example.test');

        $activityA = Activity::query()->create([
            'direction_id' => $directionA->id,
            'created_by' => $directorA->id,
            'title' => 'Activity A',
            'status' => 'DRAFT',
        ]);

        $activityB = Activity::query()->create([
            'direction_id' => $directionB->id,
            'created_by' => $directorB->id,
            'title' => 'Activity B',
            'status' => 'DRAFT',
        ]);

        $this->assertTrue(Gate::forUser($directorA)->allows('view', $activityA));
        $this->assertFalse(Gate::forUser($directorA)->allows('view', $activityB));
        $this->assertFalse(Gate::forUser($directorA)->allows('update', $activityB));
        $this->assertFalse(Gate::forUser($directorA)->allows('delete', $activityB));

        $this->assertTrue(Gate::forUser($directorA)->allows('view', $directionA));
        $this->assertFalse(Gate::forUser($directorA)->allows('view', $directionB));
        $this->assertFalse(Gate::forUser($directorA)->allows('update', $directionB));
    }

    private function makeUser(string $roleCode, ?Direction $direction, string $email): User
    {
        $role = Role::query()->firstOrCreate([
            'code' => $roleCode,
            'name' => $roleCode,
        ]);

        return User::query()->create([
            'role_id' => $role->id,
            'direction_id' => $direction?->id,
            'name' => 'Test',
            'firstname' => 'User',
            'email' => $email,
            'password' => bcrypt('password123'),
            'status' => 'ACTIVE',
        ]);
    }
}
