<?php

use Illuminate\Foundation\Application;

$expectedEnvironment = [
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
];

foreach ($expectedEnvironment as $name => $expectedValue) {
    $actualValue = getenv($name);
    $matches = $expectedValue === ''
        ? $actualValue === false || $actualValue === ''
        : $actualValue === $expectedValue;

    if (! $matches) {
        throw new RuntimeException('Unsafe PHPUnit database environment. Refusing to start.');
    }

    if ($expectedValue === '') {
        putenv($name);
    } else {
        putenv("{$name}={$expectedValue}");
    }

    $_ENV[$name] = $expectedValue;
    $_SERVER[$name] = $expectedValue;
}

$configCachePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'meh-phpunit-config-'.getmypid().'.php';

if (file_exists($configCachePath)) {
    throw new RuntimeException('Unable to establish isolated PHPUnit configuration.');
}

putenv('APP_CONFIG_CACHE='.$configCachePath);
$_ENV['APP_CONFIG_CACHE'] = $configCachePath;
$_SERVER['APP_CONFIG_CACHE'] = $configCachePath;

require_once dirname(__DIR__).'/vendor/autoload.php';

$previousApplication = Application::getInstance();
new Application(dirname(__DIR__));

try {
    $databaseConfiguration = require dirname(__DIR__).'/config/database.php';
} finally {
    Application::setInstance($previousApplication);
}

$sqliteConnection = $databaseConfiguration['connections']['sqlite'] ?? null;

if (
    ($databaseConfiguration['default'] ?? null) !== 'sqlite'
    || ! is_array($sqliteConnection)
    || ($sqliteConnection['driver'] ?? null) !== 'sqlite'
    || ($sqliteConnection['database'] ?? null) !== ':memory:'
    || ! in_array($sqliteConnection['url'] ?? null, [null, ''], true)
) {
    throw new RuntimeException('Unsafe PHPUnit database configuration. Refusing to start.');
}
