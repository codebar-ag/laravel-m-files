<?php

return [
    'auth' => [
        'url' => env('M_FILES_URL'),
        'username' => env('M_FILES_USERNAME'),
        'password' => env('M_FILES_PASSWORD'),
        'expiration' => (int) env('M_FILES_EXPIRATION_SECONDS', 3600),
        'session_id' => env('M_FILES_SESSION_ID'),
    ],

    'vault_guid' => env('M_FILES_VAULT_GUID'),

    'cache_driver' => env('M_FILES_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),

    /*
    |--------------------------------------------------------------------------
    | Transport
    |--------------------------------------------------------------------------
    |
    | Timeouts are in seconds; without them Guzzle waits indefinitely and a single
    | unresponsive vault can pin every PHP worker. Retries only replay failures that
    | are safe to replay — connection errors, 408/429/503, and 5xx on idempotent
    | methods — so a document-creating POST is never sent twice. Set "tries" to 1 to
    | disable retries entirely.
    |
    */

    'http' => [
        'connect_timeout' => (int) env('M_FILES_CONNECT_TIMEOUT', 10),
        'timeout' => (int) env('M_FILES_TIMEOUT', 60),
        'tries' => (int) env('M_FILES_TRIES', 3),
        'retry_interval' => (int) env('M_FILES_RETRY_INTERVAL_MS', 500),
    ],

];
