<?php

use Illuminate\Http\Request;
use Laravel\Vapor\Runtime\HttpKernel;

require __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

$app = require_once __DIR__ . '/../bootstrap/app.php';

$handler = new HttpKernel($app);

$response = $handler->handle(Request::capture());

$response->send();
