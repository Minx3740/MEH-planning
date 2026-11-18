<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

final class AdminUserSeederTest extends TestCase
{
    public function test_it_creates_the_configured_admin_with_a_hashed_password(): void
    {
        $adminRole = $this->createAdminRole();
        $configuration = self::validConfiguration();
        config()->set('meh.initial_admin', $configuration);

        app(AdminUserSeeder::class)->run();

        $admin = User::query()->where('email', $configuration['email'])->firstOrFail();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($configuration['name'], $admin->name);
        $this->assertSame($configuration['firstname'], $admin->firstname);
        $this->assertSame($configuration['email'], $admin->email);
        $this->assertSame($adminRole->getKey(), $admin->role_id);
        $this->assertSame('ADMIN', $admin->role->code);
        $this->assertNull($admin->direction_id);
        $this->assertSame('ACTIVE', $admin->status);
        $this->assertNull($admin->phone);
        $this->assertNull($admin->photo);
        $this->assertNull($admin->last_login);
        $this->assertTrue(Hash::check($configuration['password'], $admin->password));
        $this->assertNotSame($configuration['password'], $admin->password);
        $audit = AuditLog::query()->where('entity_type', 'User')->where('entity_id', $admin->id)->firstOrFail();
        $this->assertSame('user.created', $audit->action);
        $this->assertStringNotContainsString('password', strtolower((string) $audit->description));
    }

    #[DataProvider('requiredConfigurationKeys')]
    public function test_it_refuses_creation_when_a_required_configuration_value_is_missing(
        string $key,
        string $environmentVariable,
    ): void {
        $configuration = self::validConfiguration();
        unset($configuration[$key]);

        $this->assertConfigurationRejected(
            $configuration,
            "Set {$environmentVariable} to create the initial ADMIN account.",
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function requiredConfigurationKeys(): array
    {
        return [
            'name' => ['name', 'MEH_ADMIN_NAME'],
            'firstname' => ['firstname', 'MEH_ADMIN_FIRSTNAME'],
            'email' => ['email', 'MEH_ADMIN_EMAIL'],
            'password' => ['password', 'MEH_ADMIN_PASSWORD'],
        ];
    }

    #[DataProvider('invalidConfigurationValues')]
    public function test_it_rejects_invalid_values_without_exposing_configuration(
        string $key,
        string $value,
        string $expectedMessage,
    ): void {
        $configuration = self::validConfiguration();
        $configuration[$key] = $value;

        $this->assertConfigurationRejected($configuration, $expectedMessage);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    public static function invalidConfigurationValues(): array
    {
        return [
            'password too short' => [
                'password',
                'short-pass',
                'MEH_ADMIN_PASSWORD must contain at least 12 characters.',
            ],
            'name too long' => [
                'name',
                str_repeat('N', 101),
                'MEH_ADMIN_NAME must not exceed 100 characters.',
            ],
            'firstname too long' => [
                'firstname',
                str_repeat('F', 101),
                'MEH_ADMIN_FIRSTNAME must not exceed 100 characters.',
            ],
            'invalid email' => [
                'email',
                'not-an-email',
                'MEH_ADMIN_EMAIL must contain a valid email address.',
            ],
            'email too long' => [
                'email',
                self::emailExceedingColumnLength(),
                'MEH_ADMIN_EMAIL must not exceed 191 characters.',
            ],
            'blank name' => [
                'name',
                '   ',
                'Set MEH_ADMIN_NAME to create the initial ADMIN account.',
            ],
            'blank firstname' => [
                'firstname',
                '   ',
                'Set MEH_ADMIN_FIRSTNAME to create the initial ADMIN account.',
            ],
            'blank email' => [
                'email',
                '   ',
                'Set MEH_ADMIN_EMAIL to create the initial ADMIN account.',
            ],
            'blank password' => [
                'password',
                '   ',
                'Set MEH_ADMIN_PASSWORD to create the initial ADMIN account.',
            ],
        ];
    }

    public function test_it_refuses_a_configured_email_already_used_without_changing_the_user(): void
    {
        $adminRole = $this->createAdminRole();
        $otherRole = Role::query()->create([
            'code' => 'SG',
            'name' => 'Test SG',
            'description' => 'Test role',
        ]);
        $configuration = self::validConfiguration();
        $existingUser = User::query()->create([
            'role_id' => $otherRole->getKey(),
            'direction_id' => null,
            'name' => 'Existing',
            'firstname' => 'User',
            'email' => $configuration['email'],
            'password' => 'Existing-user-password',
            'status' => 'INACTIVE',
        ]);
        $existingUser->refresh();
        $originalAttributes = $existingUser->getAttributes();
        config()->set('meh.initial_admin', $configuration);

        try {
            app(AdminUserSeeder::class)->run();
            $this->fail('Expected an exception for an email already in use.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Cannot create the initial ADMIN: the configured email is already in use.',
                $exception->getMessage(),
            );
            $this->assertNoConfiguredValuesInMessage($exception->getMessage(), $configuration);
        }

        $this->assertSame($originalAttributes, $existingUser->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(0, User::query()->where('role_id', $adminRole->getKey())->count());
    }

    public function test_it_does_not_create_or_modify_an_admin_when_one_already_exists(): void
    {
        $adminRole = $this->createAdminRole();
        $existingAdmin = User::query()->create([
            'role_id' => $adminRole->getKey(),
            'direction_id' => null,
            'name' => 'Existing Admin',
            'firstname' => 'Existing',
            'email' => 'existing-admin@example.test',
            'password' => 'Existing-admin-password',
            'status' => 'INACTIVE',
        ]);
        $existingAdmin->refresh();
        $originalAttributes = $existingAdmin->getAttributes();
        config()->set('meh.initial_admin', self::validConfiguration());

        app(AdminUserSeeder::class)->run();

        $this->assertSame($originalAttributes, $existingAdmin->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(1, User::query()->where('role_id', $adminRole->getKey())->count());
        $this->assertDatabaseMissing('users', ['email' => 'initial.admin@example.test']);
    }

    /**
     * @param  array<string, string>  $configuration
     */
    private function assertConfigurationRejected(array $configuration, string $expectedMessage): void
    {
        config()->set('meh.initial_admin', $configuration);
        $this->createAdminRole();

        try {
            app(AdminUserSeeder::class)->run();
            $this->fail('Expected invalid administrator configuration to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString($expectedMessage, $exception->getMessage());
            $this->assertNoConfiguredValuesInMessage($exception->getMessage(), $configuration);
        }

        $this->assertDatabaseCount('users', 0);
    }

    /**
     * @param  array<string, string>  $configuration
     */
    private function assertNoConfiguredValuesInMessage(string $message, array $configuration): void
    {
        foreach ($configuration as $value) {
            $this->assertStringNotContainsString($value, $message);
        }
    }

    private function createAdminRole(): Role
    {
        return Role::query()->create([
            'code' => 'ADMIN',
            'name' => 'Test Admin',
            'description' => 'Role for AdminUserSeeder tests',
        ]);
    }

    /**
     * @return array{name: string, firstname: string, email: string, password: string}
     */
    private static function validConfiguration(): array
    {
        return [
            'name' => 'Initial Administrator',
            'firstname' => 'Test Admin',
            'email' => 'initial.admin@example.test',
            'password' => 'TestSecret-Password-2026!',
        ];
    }

    private static function emailExceedingColumnLength(): string
    {
        return str_repeat('a', 64).'@'.str_repeat('b', 63).'.'.str_repeat('c', 60).'.xx';
    }
}
