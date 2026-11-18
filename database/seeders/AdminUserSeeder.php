<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\AccountAuditService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    private const MIN_PASSWORD_LENGTH = 12;

    /**
     * Create the first administrator from deployment-provided configuration.
    */
    public function run(): void
    {
        $adminRole = Role::query()
            ->where('code', 'ADMIN')
            ->first();

        if ($adminRole === null) {
            throw new RuntimeException('The ADMIN role is missing. Run RoleSeeder before AdminUserSeeder.');
        }

        if (User::query()->where('role_id', $adminRole->getKey())->exists()) {
            return;
        }

        $configuration = config('meh.initial_admin');

        if (! is_array($configuration)) {
            throw new RuntimeException('Initial administrator configuration is missing.');
        }

        $name = $this->requiredValue($configuration, 'name', 'MEH_ADMIN_NAME');
        $firstname = $this->requiredValue($configuration, 'firstname', 'MEH_ADMIN_FIRSTNAME');
        $email = $this->requiredValue($configuration, 'email', 'MEH_ADMIN_EMAIL');
        $password = $this->requiredValue($configuration, 'password', 'MEH_ADMIN_PASSWORD', false);

        if (mb_strlen($name) > 100) {
            throw new RuntimeException('MEH_ADMIN_NAME must not exceed 100 characters.');
        }

        if (mb_strlen($firstname) > 100) {
            throw new RuntimeException('MEH_ADMIN_FIRSTNAME must not exceed 100 characters.');
        }

        if (mb_strlen($email) > 191) {
            throw new RuntimeException('MEH_ADMIN_EMAIL must not exceed 191 characters.');
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('MEH_ADMIN_EMAIL must contain a valid email address.');
        }

        if (mb_strlen($password) < self::MIN_PASSWORD_LENGTH) {
            throw new RuntimeException(
                'MEH_ADMIN_PASSWORD must contain at least '.self::MIN_PASSWORD_LENGTH.' characters.',
            );
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new RuntimeException('Cannot create the initial ADMIN: the configured email is already in use.');
        }

        DB::transaction(function () use ($adminRole, $name, $firstname, $email, $password): void {
            $admin = User::query()->create([
                'role_id' => $adminRole->getKey(),
                'direction_id' => null,
                'name' => $name,
                'firstname' => $firstname,
                'email' => $email,
                'password' => $password,
            ]);

            app(AccountAuditService::class)->record(null, 'user.created', $admin, [
                'provisioning' => 'initial_admin',
                'after' => [
                    'name' => $admin->name,
                    'firstname' => $admin->firstname,
                    'email' => $admin->email,
                    'role_id' => $admin->role_id,
                    'direction_id' => $admin->direction_id,
                    'status' => $admin->status,
                ],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $configuration
     */
    private function requiredValue(
        array $configuration,
        string $key,
        string $environmentVariable,
        bool $trim = true,
    ): string {
        $value = $configuration[$key] ?? null;

        if (! is_string($value)) {
            throw new RuntimeException("Set {$environmentVariable} to create the initial ADMIN account.");
        }

        $value = $trim ? trim($value) : $value;

        if (trim($value) === '') {
            throw new RuntimeException("Set {$environmentVariable} to create the initial ADMIN account.");
        }

        return $value;
    }
}
