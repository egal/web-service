<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () {
    return response(
        json_encode([
            'message' => 'Hello, world!',
            'app_name' => config('app.name'),
            'app_service_name' => config('app.service_name'),
        ]),
        200,
        ['Content-Type' => 'application/json']
    );
});

$router->addRoute(
    ['GET', 'POST', 'PUT', 'DELETE'],
    '/{service}/{model}/{action}[/{id}]',
    function (\Illuminate\Http\Request $httpRequest, string $service, string $model, string $action, ?string $id = null) {
        return \App\HttpEgalBridge\Response::toHttpResponse(
            \App\HttpEgalBridge\Request::fromHttpRequest($httpRequest, $service, $model, $action, $id)->call()
        );
    }
);
