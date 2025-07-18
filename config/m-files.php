<?php

return [
    'auth' => [
        'url' => env('M_FILES_URL'),
        'username' => env('M_FILES_USERNAME'),
        'password' => env('M_FILES_PASSWORD'),
        'expiration' => env('M_FILES_EXPIRATION_SECONDS', '3600'), // Default to 1 hour
        'session_id' => env('M_FILES_SESSION_ID'),
    ],

    'vault_guid' => env('M_FILES_VAULT_GUID'),

    'cache_driver' => env('M_FILES_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),

];
