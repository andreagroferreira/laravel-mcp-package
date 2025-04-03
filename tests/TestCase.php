<?php

namespace WizardingCode\MCPServer\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use WizardingCode\MCPServer\Providers\MCPServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            MCPServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set MCP package configuration
        $app['config']->set('mcp.routes_enabled', true);
        $app['config']->set('mcp.logging.enabled', false);
    }
}