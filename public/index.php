<?php

define('LARAVEL_START', microtime(true));

// Register the auto loader that ships with composer...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
$app = require_once __DIR__.'/../bootstrap/app.php';

with($kernel = $app->make(Illuminate\Contracts\Http\Kernel::class))
    ->handle($request = Illuminate\Http\Request::capture())
    ->send();

$kernel->terminate($request);
