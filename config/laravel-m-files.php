<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cache driver
    |--------------------------------------------------------------------------
    | You may like to define a different cache driver than the default Laravel cache driver.
    |
    */

    'cache_driver' => env('M_FILES_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),

    /*
    |--------------------------------------------------------------------------
    | Requests timeout
    |--------------------------------------------------------------------------
    | This variable is optional and only used if you want to set the request timeout manually.
    |
    */

    'timeout' => env('M_FILES_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | DocuWare Credentials
    |--------------------------------------------------------------------------
    |
    | Before you can communicate with the M-Files REST-API it is necessary
    | to enter your credentials. You should specify a url containing the
    | scheme and hostname. In addition add your username and password.
    |
    */

    'credentials' => [
        'url' => env('M_FILES_URL'),
        'username' => env('M_FILES_USERNAME'),
        'password' => env('M_FILES_PASSWORD'),
    ],
];
