<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Shiprocket Credentilas
    |--------------------------------------------------------------------------
    |
    | Here you can set the default shiprocket credentilas. However, you can pass the credentials while connecting to shiprocket client
    | 
    */

    'credentials' => [
        'email' => env('SHIPROCKET_EMAIL', 'kiaasa.pr@gmail.com'),
        'password' => env('SHIPROCKET_PASSWORD', 'IMP@rugs10'),
    ],


    /*
    |--------------------------------------------------------------------------
    | Default output response type
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the output response you need.
    | 
    | Supported: "collection" , "object", "array"
    | 
    */

    'responseType' => 'collection',
];
