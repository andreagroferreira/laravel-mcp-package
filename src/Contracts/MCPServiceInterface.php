<?php

namespace WizardingCode\MCPServer\Contracts;

use Illuminate\Http\Request;
use WizardingCode\MCPServer\Types\MessageType;
use WizardingCode\MCPServer\Types\Resource;
use WizardingCode\MCPServer\Types\Tool;

interface MCPServiceInterface
{
    /**
     * Initialize the MCP server with client capabilities
     *
     * @param array $clientInfo Information about the client
     * @param array $capabilities Client capabilities
     * @param string $protocolVersion Protocol version
     * @return array Server initialization result
     */
    public function initialize(array $clientInfo, array $capabilities, string $protocolVersion): array;

    /**
     * Register a resource in the MCP server
     *
     * @param string $name Resource name
     * @param string $uri Resource URI
     * @param callable $readCallback Callback to read the resource
     * @param array $metadata Optional metadata
     * @return $this
     */
    public function registerResource(string $name, string $uri, callable $readCallback, array $metadata = []): self;

    /**
     * Register a resource template in the MCP server
     *
     * @param string $name Template name
     * @param string $uriTemplate URI template
     * @param callable $readCallback Callback to read resources matching the template
     * @param array $metadata Optional metadata
     * @param callable|null $listCallback Optional callback to list all resources matching the template
     * @param array $completeCallbacks Optional callbacks for completion of template variables
     * @return $this
     */
    public function registerResourceTemplate(
        string $name, 
        string $uriTemplate, 
        callable $readCallback, 
        array $metadata = [],
        ?callable $listCallback = null,
        array $completeCallbacks = []
    ): self;

    /**
     * Register a tool in the MCP server
     *
     * @param string $name Tool name
     * @param callable $callback Tool callback
     * @param array $inputSchema Input schema for the tool
     * @param string|null $description Optional description
     * @return $this
     */
    public function registerTool(string $name, callable $callback, array $inputSchema = [], ?string $description = null): self;

    /**
     * Register a prompt in the MCP server
     *
     * @param string $name Prompt name
     * @param callable $callback Prompt callback
     * @param array $argsSchema Arguments schema for the prompt
     * @param string|null $description Optional description
     * @return $this
     */
    public function registerPrompt(string $name, callable $callback, array $argsSchema = [], ?string $description = null): self;

    /**
     * List all registered resources
     *
     * @param Request $request
     * @return array List of resources
     */
    public function listResources(Request $request): array;

    /**
     * List all registered resource templates
     *
     * @param Request $request
     * @return array List of resource templates
     */
    public function listResourceTemplates(Request $request): array;

    /**
     * Read a resource by URI
     *
     * @param string $uri Resource URI to read
     * @param Request $request
     * @return array Resource contents
     */
    public function readResource(string $uri, Request $request): array;

    /**
     * List all registered tools
     *
     * @param Request $request
     * @return array List of tools
     */
    public function listTools(Request $request): array;

    /**
     * Call a tool by name
     *
     * @param string $name Tool name
     * @param array $arguments Tool arguments
     * @param Request $request
     * @return array Tool result
     */
    public function callTool(string $name, array $arguments, Request $request): array;

    /**
     * List all registered prompts
     *
     * @param Request $request
     * @return array List of prompts
     */
    public function listPrompts(Request $request): array;

    /**
     * Get a prompt by name
     *
     * @param string $name Prompt name
     * @param array $arguments Prompt arguments
     * @param Request $request
     * @return array Prompt result
     */
    public function getPrompt(string $name, array $arguments, Request $request): array;

    /**
     * Handle a ping request
     *
     * @param Request $request
     * @return array Empty result
     */
    public function ping(Request $request): array;

    /**
     * Process an MCP request
     *
     * @param MessageType $messageType The message type (request, notification)
     * @param string $method The MCP method
     * @param array $params Request parameters
     * @param mixed $id Request ID for requests (null for notifications)
     * @param Request $request
     * @return array|null Response (null for notifications)
     */
    public function processMessage(MessageType $messageType, string $method, array $params, $id, Request $request): ?array;
}