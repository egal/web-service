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
                'reactor_num' => (int)env(
                    'SWOOLE_HTTP_REACTOR_NUM',
                    swoole_cpu_num() * (float)env('SWOOLE_HTTP_REACTOR_NUM_MULTIPLIER', 1)
                ),
                'worker_num' => (int)env(
                    'SWOOLE_HTTP_WORKER_NUM',
                    swoole_cpu_num() * (float)env('SWOOLE_HTTP_WORKER_NUM_MULTIPLIER', 1)
                ),
            ],
        ],

    ]

];