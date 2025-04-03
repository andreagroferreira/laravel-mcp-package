<?php

namespace WizardingCode\MCPServer\Tests\Unit;

use Illuminate\Http\Request;
use WizardingCode\MCPServer\Services\MCPService;
use WizardingCode\MCPServer\Tests\TestCase;
use WizardingCode\MCPServer\Types\MessageType;

class MCPServiceTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_initialize_with_supported_protocol_version()
    {
        // Arrange
        $service = new MCPService();
        $clientInfo = ['name' => 'Test Client', 'version' => '1.0.0'];
        $capabilities = ['resources' => []];
        $protocolVersion = MCPService::LATEST_PROTOCOL_VERSION;

        // Act
        $result = $service->initialize($clientInfo, $capabilities, $protocolVersion);

        // Assert
        $this->assertEquals($protocolVersion, $result['protocolVersion']);
        $this->assertArrayHasKey('serverInfo', $result);
        $this->assertArrayHasKey('capabilities', $result);
    }

    /**
     * @test
     */
    public function it_falls_back_to_latest_protocol_version_when_unsupported()
    {
        // Arrange
        $service = new MCPService();
        $clientInfo = ['name' => 'Test Client', 'version' => '1.0.0'];
        $capabilities = ['resources' => []];
        $protocolVersion = 'unsupported-version';

        // Act
        $result = $service->initialize($clientInfo, $capabilities, $protocolVersion);

        // Assert
        $this->assertEquals(MCPService::LATEST_PROTOCOL_VERSION, $result['protocolVersion']);
    }

    /**
     * @test
     */
    public function it_can_register_and_list_tools()
    {
        // Arrange
        $service = new MCPService();
        $request = Request::create('/', 'GET');
        
        // Act
        $service->registerTool('test-tool', function ($arguments, $request) {
            return ['content' => [['type' => 'text', 'text' => 'Test result']]];
        }, ['type' => 'object'], 'Test tool description');
        
        $result = $service->listTools($request);

        // Assert
        $this->assertCount(1, $result['tools']);
        $this->assertEquals('test-tool', $result['tools'][0]['name']);
        $this->assertEquals('Test tool description', $result['tools'][0]['description']);
    }

    /**
     * @test
     */
    public function it_can_register_and_call_tools()
    {
        // Arrange
        $service = new MCPService();
        $request = Request::create('/', 'GET');
        
        // Act
        $service->registerTool('add', function ($arguments, $request) {
            $a = $arguments['a'] ?? 0;
            $b = $arguments['b'] ?? 0;
            return [
                'content' => [
                    ['type' => 'text', 'text' => (string)($a + $b)]
                ]
            ];
        });
        
        $result = $service->callTool('add', ['a' => 2, 'b' => 3], $request);

        // Assert
        $this->assertEquals('5', $result['content'][0]['text']);
    }

    /**
     * @test
     */
    public function it_can_register_and_list_resources()
    {
        // Arrange
        $service = new MCPService();
        $request = Request::create('/', 'GET');
        
        // Act
        $service->registerResource('test-resource', 'test://resource', function ($uri, $request) {
            return [
                'contents' => [
                    ['uri' => 'test://resource', 'text' => 'Test content']
                ]
            ];
        }, ['description' => 'Test resource description']);
        
        $result = $service->listResources($request);

        // Assert
        $this->assertCount(1, $result['resources']);
        $this->assertEquals('test://resource', $result['resources'][0]['uri']);
        $this->assertEquals('test-resource', $result['resources'][0]['name']);
        $this->assertEquals('Test resource description', $result['resources'][0]['description']);
    }

    /**
     * @test
     */
    public function it_can_process_ping_request()
    {
        // Arrange
        $service = new MCPService();
        $request = Request::create('/', 'GET');
        
        // Act
        $result = $service->processMessage(
            MessageType::REQUEST,
            'ping',
            [],
            1,
            $request
        );

        // Assert
        $this->assertEquals([], $result);
    }
}