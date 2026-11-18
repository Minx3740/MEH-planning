<?php

namespace Tests\Feature;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

final class RoleSeederTest extends TestCase
{
    private const EXPECTED_ROLE_CODES = [
        'ADMIN',
        'DIRECTEUR',
        'SG',
    ];

    public function test_it_creates_only_the_expected_system_roles(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertSame(self::EXPECTED_ROLE_CODES, Role::query()->orderBy('code')->pluck('code')->all());
        $this->assertDatabaseCount('roles', 3);
        $this->assertDatabaseMissing('roles', ['code' => 'CHEF_SERVICE']);
    }

    public function test_it_is_idempotent_when_run_twice(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame(self::EXPECTED_ROLE_CODES, Role::query()->orderBy('code')->pluck('code')->all());
        $this->assertDatabaseCount('roles', 3);
    }

    public function test_it_preserves_customized_data_for_an_existing_role(): void
    {
        Role::query()->create([
            'code' => 'ADMIN',
            'name' => 'Nom personnalisé',
            'description' => 'Description personnalisée',
        ]);

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', [
            'code' => 'ADMIN',
            'name' => 'Nom personnalisé',
            'description' => 'Description personnalisée',
        ]);
        $this->assertDatabaseCount('roles', 3);
    }
}
