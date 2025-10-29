<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Determine what cross-origin operations may execute in web browsers. You
    | can adjust the allowed origins, methods, and headers as required by
    | your frontends. Update the origins list when deploying elsewhere.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://applldespachante.skalacode.com',
    ],

    'allowed_origins_patterns' => [
        '/^https?:\/\/localhost(:\d+)?$/',
        '/^https?:\/\/127\.0\.0\.1(:\d+)?$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
