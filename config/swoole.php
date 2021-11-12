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
                'log_file' => env('SWOOLE_HTTP_LOG_FILE', base_path('storage/logs/swoole_http.log')),
                'reactor_num' => (int)env(
                    'SWOOLE_HTTP_REACTOR_NUM',
                    swoole_cpu_num() * (float)env('SWOOLE_HTTP_REACTOR_NUM_MULTIPLIER')
                ),
                'worker_num' => (int)env(
                    'SWOOLE_HTTP_WORKER_NUM',
                    swoole_cpu_num() * (float)env('SWOOLE_HTTP_WORKER_NUM_MULTIPLIER')
                ),
            ],
        ],

    ]

];