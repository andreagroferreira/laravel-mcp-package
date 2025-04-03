<?php

namespace WizardingCode\MCPServer\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use WizardingCode\MCPServer\Providers\MCPServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/bootstrap.php';

        // Set bootstrapPath to avoid issues with the bootstrap/cache directory
        $app->useBootstrapPath(__DIR__.'/../bootstrap');
        
        // Bootstrap the application
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        // Register the service provider
        $app->register(MCPServiceProvider::class);

        return $app;
    }
}