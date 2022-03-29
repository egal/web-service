<?php

return [

    'http' => [

        /*
        |--------------------------------------------------------------------------
        | HTTP server configurations.
        |--------------------------------------------------------------------------
        |
        | @see https://www.swoole.co.uk/docs/modules/swoole-server/configuration
        |
        */
        'server' => [

            'host' => env('SWOOLE_HTTP_HOST', '0.0.0.0'),
            'port' => (int)env('SWOOLE_HTTP_PORT', 8080),

            'options' => [
                'package_max_length' => env('SWOOLE_PACKAGE_MAX_LENGTH', 5 * 1024 * 1024),
                'reactor_num' => env('SWOOLE_AUTO_CONFIG', false)
                    ? (int)env('SWOOLE_HTTP_REACTOR_NUM', swoole_cpu_num() * (float)env('SWOOLE_HTTP_REACTOR_NUM_MULTIPLIER', 1))
                    : (int)env('SWOOLE_HTTP_REACTOR_NUM',1),
                'worker_num' => env('SWOOLE_AUTO_CONFIG', false)
                    ? (int)env('SWOOLE_HTTP_WORKER_NUM', swoole_cpu_num() * (float)env('SWOOLE_HTTP_WORKER_NUM_MULTIPLIER', 1))
                    : (int)env('SWOOLE_HTTP_WORKER_NUM',1),
            ],

        ],

    ]

];