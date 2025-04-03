<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Server Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the MCP Server package.
    |
    */

    // Enable or disable the routes provided by the package
    'routes_enabled' => true,

    // The route prefix for the MCP API endpoints
    'route_prefix' => 'api/mcp',

    // Enable or disable migrations
    'migrations_enabled' => true,

    // Authentication settings
    'auth' => [
        'enabled' => true,
        'guard' => 'api',
        'provider' => 'users',
    ],

    // Registered MCP services
    'services' => [
        // Example:
        // 'example' => [
        //     'handler' => \App\Services\MCP\ExampleService::class,
        //     'config' => [
        //         'option1' => 'value1',
        //     ],
        // ],
    ],

    // Default response format
    'response_format' => 'json',

    // Logging configuration
    'logging' => [
        'enabled' => true,
        'channel' => env('MCP_LOG_CHANNEL', 'stack'),
    ],
];