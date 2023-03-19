<?php

return [
    'composition' => [
        'model' => [
            app()->getNamespace() . 'Models\\',
            ''
        ],
        'request' => [
            app()->getNamespace() . 'Http\Requests\\',
            'Request'
        ],
        'controller' => [
            app()->getNamespace() . 'Http\Controllers\\',
            'Controller',
        ],
        'job' =>[
            app()->getNamespace() . 'Jobs\\',
            'Job'
        ]
    ],
    'namespaces' => [
        'models' => 'App\\Models\\',
        'controllers' => 'App\\Http\\Controllers\\',
        'jobs' => 'App\\Jobs\\'
    ],
    'auth' => [
        'guard' => 'api',
    ],
    'api' => [
        'limit' => 10,
        'pagination_disabled' => false
    ],
    'specs' => [
        'info' => [
            'title' => env('APP_NAME'),
            'description' => null,
            'terms_of_service' => null,
            'contact' => [
                'name' => null,
                'url' => null,
                'email' => null,
            ],
            'license' => [
                'name' => null,
                'url' => null,
            ],
            'version' => '1.0.0',
        ],
        'servers' => [
            ['url' => env('APP_URL').'/api', 'description' => 'Default Environment'],
        ],
        'tags' => [],
    ],
    'transactions' => [
        'enabled' => false,
    ],
    'search' => [
        'case_sensitive' => true, // TODO: set to "false" by default in 3.0 release
    ],
    'tracer' => 'App\\Loggers\\LogTrace',
    /*
     |--------------------------------------------------------------------------
     | Max Nested Depth
     |--------------------------------------------------------------------------
     |
     | This value is the maximum depth of nested filters.
     | You will most likely need this to be maximum at 1, but
     | you can increase this number, if necessary. Please
     | be aware that the depth generate dynamic rules and can slow
     | your application if someone sends a request with thousands of nested
     | filters.
     |
     */
    'max_nested_depth' => 1,
    'use_validated' => false,
];
