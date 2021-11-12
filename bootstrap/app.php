<?php

require_once __DIR__ . '/../vendor/autoload.php';

(new Laravel\Lumen\Bootstrap\LoadEnvironmentVariables(
    dirname(__DIR__)
))->bootstrap();

date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

$app = new Egal\Core\Application(dirname(__DIR__));

$app->singleton(Illuminate\Contracts\Debug\ExceptionHandler::class, App\Exceptions\Handler::class);
$app->singleton(Egal\Core\Bus\Bus::class, static fn (): Egal\Core\Bus\Bus => Egal\Core\Bus\BusCreator::createBus());

$app->configure('app');
$app->configure('queue');
$app->configure('swoole');

$app->register(App\Providers\AppServiceProvider::class);

$app->router->group([
    'namespace' => 'App\Http\Controllers',
], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
