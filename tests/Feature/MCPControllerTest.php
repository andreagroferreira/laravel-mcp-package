<?php

namespace WizardingCode\MCPServer\Tests\Feature;

use Illuminate\Support\Facades\Route;
use WizardingCode\MCPServer\Facades\MCP;
use WizardingCode\MCPServer\Tests\TestCase;

class MCPControllerTest extends TestCase
{
    /** @test */
    public function it_returns_404_for_unknown_service()
    {
        // Make request to a non-existent service
        $response = $this->postJson('/api/mcp/unknown-service', ['data' => 'test']);

        // Assert response
        $response->assertStatus(404)
                 ->assertJson(['error' => 'Service not found: unknown-service']);
    }

    /** @test */
    public function it_can_process_request_for_registered_service()
    {
        // Override auth middleware for testing
        $this->app['config']->set('mcp.auth.enabled', false);
        
        // Create a mock service class
        $mockServiceClass = get_class(createMockService(['result' => 'success', 'data' => 'test-response']));
        
        // Register the service
        MCP::registerService('test-service', $mockServiceClass);
        
        // Make request to the service
        $response = $this->postJson('/api/mcp/test-service', ['data' => 'test-request']);
        
        // Assert response
        $response->assertStatus(200)
                 ->assertJson([
                     'result' => 'success',
                     'data' => 'test-response'
                 ]);
    }

    /** @test */
    public function it_verifies_routes_are_registered()
    {
        // Check if the route exists
        $this->assertTrue(Route::has('mcp.service.handle'));
    }
}