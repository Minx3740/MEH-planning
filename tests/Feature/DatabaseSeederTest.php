<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class DatabaseSeederTest extends TestCase
{
    public function test_it_orchestrates_roles_and_admin_without_demo_or_artificial_data(): void
    {
        $configuration = [
            'name' => 'Initial Administrator',
            'firstname' => 'Test Admin',
            'email' => 'initial.admin@example.test',
            'password' => 'TestSecret-Password-2026!',
        ];
        config()->set('meh.initial_admin', $configuration);

        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('groups', 0);
        $this->assertDatabaseCount('directions', 0);

        $this->seed(DatabaseSeeder::class);

        $this->assertSame(
            ['ADMIN', 'DIRECTEUR', 'SG'],
            Role::query()->orderBy('code')->pluck('code')->all(),
        );
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseMissing('roles', ['code' => 'CHEF_SERVICE']);

        $admin = User::query()->where('email', $configuration['email'])->firstOrFail();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($configuration['name'], $admin->name);
        $this->assertSame($configuration['firstname'], $admin->firstname);
        $this->assertSame($configuration['email'], $admin->email);
        $this->assertSame('ADMIN', $admin->role->code);
        $this->assertSame($admin->role_id, $admin->role->getKey());
        $this->assertNull($admin->direction_id);
        $this->assertSame('ACTIVE', $admin->status);
        $this->assertTrue(Hash::check($configuration['password'], $admin->password));
        $this->assertNotSame($configuration['password'], $admin->password);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);

        $this->assertDatabaseCount('groups', 0);
        $this->assertDatabaseCount('directions', 0);
    }
}
