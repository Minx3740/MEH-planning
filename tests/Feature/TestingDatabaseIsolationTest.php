<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Framework\Attributes\Depends;
use Tests\TestCase;

final class TestingDatabaseIsolationTest extends TestCase
{
    private const EXPECTED_APPLICATION_TABLES = [
        'account_requests',
        'activities',
        'activity_attachments',
        'activity_people',
        'activity_reschedules',
        'activity_share_activities',
        'activity_shares',
        'activity_versions',
        'audit_logs',
        'directions',
        'exercice_comptes',
        'groups',
        'notifications',
        'password_reset_tokens',
        'roles',
        'security_reports',
        'share_invitations',
        'user_sessions',
        'users',
    ];

    public function test_normal_migrations_create_the_expected_schema_on_in_memory_sqlite(): int
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();

        $this->assertSame('sqlite', $connection->getDriverName());
        $this->assertSame(':memory:', $connection->getDatabaseName());
        $this->assertSame('sqlite', $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
        $this->assertSame(self::EXPECTED_APPLICATION_TABLES, $this->applicationTableNames());

        DB::table('cache')->insert([
            'key' => 'sqlite_lifecycle_rollback_probe',
            'value' => 'test-only',
            'expiration' => now()->addMinute()->getTimestamp(),
        ]);

        return spl_object_id($pdo);
    }

    #[Depends('test_normal_migrations_create_the_expected_schema_on_in_memory_sqlite')]
    public function test_schema_and_pdo_survive_application_recreation_and_transaction_rollback(int $pdoId): void
    {
        $pdo = DB::connection()->getPdo();

        $this->assertSame($pdoId, spl_object_id($pdo));
        $this->assertSame(self::EXPECTED_APPLICATION_TABLES, $this->applicationTableNames());
        $this->assertDatabaseMissing('cache', [
            'key' => 'sqlite_lifecycle_rollback_probe',
        ]);
    }

    /**
     * @return list<string>
     */
    private function applicationTableNames(): array
    {
        $frameworkTables = [
            'cache',
            'cache_locks',
            'failed_jobs',
            'job_batches',
            'jobs',
            'migrations',
        ];

        $tableNames = array_map(
            static fn (object $table): string => $table->name,
            DB::select("select name from sqlite_master where type = 'table' and name not like 'sqlite_%'"),
        );

        $applicationTables = array_values(array_diff($tableNames, $frameworkTables));
        sort($applicationTables);

        return $applicationTables;
    }
}
