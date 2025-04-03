<?php

namespace WizardingCode\MCPServer\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use WizardingCode\MCPServer\Contracts\MCPServiceInterface;
use WizardingCode\MCPServer\Services\MCPService;

class MCPServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/mcp.php',
            'mcp'
        );

        // Register the main service as a singleton
        $this->app->singleton(MCPServiceInterface::class, function ($app) {
            return new MCPService($app['config']->get('mcp', []));
        });

        // Register facade accessor
        $this->app->bind('mcp', function ($app) {
            return $app->make(MCPServiceInterface::class);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/mcp.php' => config_path('mcp.php'),
        ], 'mcp-config');

        // Load routes if enabled
        if ($this->app['config']->get('mcp.routes_enabled', true)) {
            $this->registerRoutes();
        }

        // Load migrations if any and if enabled
        if ($this->app['config']->get('mcp.migrations_enabled', true)) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
        }
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        });
    }

    /**
     * Get the route group configuration.
     *
     * @return array
     */
    protected function routeConfiguration()
    {
        return [
            'prefix' => config('mcp.route_prefix', 'api/mcp'),
            'middleware' => config('mcp.route_middleware', ['api']),
        ];
    }
}