<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use PDO;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    private static ?PDO $testPdo = null;

    private static ?bool $migrationsSucceeded = null;

    public function createApplication()
    {
        $app = parent::createApplication();
        $sqliteConnection = $app['config']->get('database.connections.sqlite');

        if (
            $app['config']->get('database.default') !== 'sqlite'
            || ! is_array($sqliteConnection)
            || ($sqliteConnection['driver'] ?? null) !== 'sqlite'
            || ($sqliteConnection['database'] ?? null) !== ':memory:'
            || ! in_array($sqliteConnection['url'] ?? null, [null, ''], true)
        ) {
            throw new RuntimeException('Unsafe PHPUnit database configuration. Refusing to run Laravel tests.');
        }

        $app['config']->set('database.connections', [
            'sqlite' => $sqliteConnection,
        ]);

        $database = $app->make('db');
        $database->extend('sqlite', static function (array $config, string $name): SQLiteConnection {
            self::$testPdo ??= (new SQLiteConnector)->connect($config);

            return new SQLiteConnection(
                self::$testPdo,
                $config['database'],
                $config['prefix'] ?? '',
                $config,
            );
        });

        $app->singleton(
            'db.schema',
            static fn (Application $app): SchemaBuilder => $app['db']->connection('sqlite')->getSchemaBuilder(),
        );

        $connection = $database->connection('sqlite');
        $pdo = $connection->getPdo();

        if (
            $connection->getDatabaseName() !== ':memory:'
            || $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite'
        ) {
            throw new RuntimeException('PHPUnit did not establish the isolated SQLite in-memory connection.');
        }

        if (self::$migrationsSucceeded === false) {
            throw new RuntimeException('The isolated PHPUnit database migration previously failed.');
        }

        if (self::$migrationsSucceeded === null) {
            self::$migrationsSucceeded = false;
            $database->setDefaultConnection('sqlite');

            if ($app->make(Kernel::class)->call('migrate', ['--database' => 'sqlite', '--force' => true]) !== 0) {
                throw new RuntimeException('Migrations failed on the isolated PHPUnit database.');
            }

            self::$migrationsSucceeded = true;
        }

        return $app;
    }
}
