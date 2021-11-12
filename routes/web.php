<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', function () {
    return response(
        json_encode([
            'message' => 'Hello, world!',
            'project_name' => config('app.name'),
            'service_name' => config('app.service_name', 'web'),
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
            \App\HttpEgalBridge\Request::fromHttpRequest($service, $model, $action, $id, $httpRequest)->call()
        );
    }
);
