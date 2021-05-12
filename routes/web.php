<?php

use App\Http\Controllers\LatestController;
use App\Http\Controllers\VersionOneController;
use App\Http\Controllers\VersionZeroController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Router;

/** @var Router $router */

$router->get('/', function () use ($router) {
    $projectName = env('PROJECT_NAME');
    $serviceName = env('SERVICE_NAME', env('APP_NAME', 'web-service'));
    return "Hello, it's $projectName/$serviceName!";
});

// CORS
$router->options('/{x:.*}', function (Response $response) use ($router) {
    return $response->setContent('Hello, Axios!')->withHeaders(
        [
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => '*',
        ]
    );
});

$mainAction = function (string $routeLine, Request $request) {
    switch ($request->header('Version', 'latest')) {
        default:
        case 'latest':
        case '2':
            return (new LatestController())->__invoke($routeLine, $request);
        case '1':
            return (new VersionOneController())->__invoke($routeLine, $request);
        case '0':
            return (new VersionZeroController())->__invoke($routeLine, $request);
    }
};

$router->post('/{routeLine:.*}', $mainAction);
$router->get('/{routeLine:.*}', $mainAction);
$router->put('/{routeLine:.*}', $mainAction);
$router->delete('/{routeLine:.*}', $mainAction);
