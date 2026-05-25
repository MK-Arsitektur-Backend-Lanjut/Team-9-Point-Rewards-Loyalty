<?php

return [
    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('QUEUE_DB_CONNECTION', null),
            'table' => env('QUEUE_TABLE', 'jobs'),
            'retry_after' => 90,
            'after_commit' => false,
        ],
    ],

    'failed' => [
        'database' => env('QUEUE_FAILED_DB_CONNECTION', 'pgsql'),
        'table' => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
    ],
];
