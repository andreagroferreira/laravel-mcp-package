<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Filesystem\Filesystem;

require_once __DIR__.'/../vendor/autoload.php';

// Create a new Laravel application
$app = new Application(
    realpath(__DIR__.'/../')
);

// Configure essential components
$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    Illuminate\Foundation\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    Illuminate\Foundation\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    Illuminate\Foundation\Exceptions\Handler::class
);

// Register the config service provider
$app->singleton('config', function () {
    return new Illuminate\Config\Repository([
        'app' => [
            'name' => 'Laravel MCP Server Testing',
            'env' => 'testing',
            'debug' => true,
            'key' => 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=',
            'providers' => [
                Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
                Illuminate\Database\DatabaseServiceProvider::class,
                Illuminate\Filesystem\FilesystemServiceProvider::class,
                Illuminate\Cookie\CookieServiceProvider::class,
                Illuminate\Session\SessionServiceProvider::class,
                Illuminate\View\ViewServiceProvider::class,
                Illuminate\Routing\RoutingServiceProvider::class,
            ],
        ],
        'database' => [
            'default' => 'sqlite',
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                    'prefix' => '',
                ],
            ],
        ],
        'mcp' => [
            'routes_enabled' => true,
            'logging' => [
                'enabled' => false
            ],
            'route_prefix' => 'api/mcp',
            'route_middleware' => ['api'],
        ],
        'view' => [
            'paths' => [
                __DIR__.'/../resources/views',
            ],
            'compiled' => __DIR__.'/../bootstrap/cache/views',
        ],
        'cache' => [
            'default' => 'array',
            'stores' => [
                'array' => [
                    'driver' => 'array',
                ],
            ],
        ],
    ]);
});

// Register the filesystem accessor
$app->singleton('files', function () {
    return new Filesystem;
});

// Prevent package discovery during testing
$app->instance('env', 'testing');

// Enable environment variables
$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__.'/..');
try {
    $dotenv->load();
} catch (\Exception $e) {
    // .env file may not exist, which is fine for testing
}

// Mock the package manifest to avoid file system access
$app->instance(Illuminate\Foundation\PackageManifest::class, new class($app) extends Illuminate\Foundation\PackageManifest {
    public function __construct($app) {
        // Empty constructor to avoid hitting the file system
    }
    
    public function getManifest() {
        return []; // Return empty manifest
    }
    
    public function getServiceProviders() {
        return [];
    }
    
    public function getAliases() {
        return [];
    }
});

// Setup Facades
Facade::setFacadeApplication($app);

return $app;