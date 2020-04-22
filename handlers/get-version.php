<?php

use Illuminate\Http\Request;

$router->get('/', function (Request $request) use ($router) {
    return $router->app->version();
});
