<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel CORS
    |--------------------------------------------------------------------------
    |
    | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
    | to accept any value.
    |
    */

    'supportsCredentials'       => env('CORS_SUPPORTS_CREDENTIALS', true),
    'allowedOrigins'            => explode(',', env('CORS_ALLOWEDORIGINS', '*')),
    'allowedOriginsPatterns'    => explode(',', env('CORS_ALLOWED_ORIGINS_PATTERNS', '')),
    'allowedHeaders'            => explode(',', env('CORS_ALLOWED_HEADERS', '*')),
    'allowedMethods'            => explode(',', env('CORS_ALLOWED_METHODS', '*')),
    'exposedHeaders'            => explode(',', env('CORS_EXPOSED_HEADERS', '')),
    'maxAge'                    => explode('CORS_MAX_AGE', 0),

];
