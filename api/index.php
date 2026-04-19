<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$databasePath = realpath(__DIR__ . '/../database/database.sqlite') ?: (__DIR__ . '/../database/database.sqlite');

$defaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_KEY' => 'base64:WzR8GYepvG3ByTSXzUoSzZrzu/w9i4NX3/P/W6Ofjrs=',
    'APP_URL' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'ASSET_URL' => 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $databasePath,
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'LOG_CHANNEL' => 'stderr',
    'MAIL_MAILER' => 'log',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false && ! array_key_exists($key, $_ENV) && ! array_key_exists($key, $_SERVER)) {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
