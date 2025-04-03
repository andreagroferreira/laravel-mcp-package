<?php

namespace WizardingCode\MCPServer\Facades;

use Illuminate\Support\Facades\Facade;
use WizardingCode\MCPServer\Contracts\MCPServiceInterface;

/**
 * @method static \WizardingCode\MCPServer\Contracts\MCPServiceInterface registerResource(string $name, string $uri, callable $readCallback, array $metadata = [])
 * @method static \WizardingCode\MCPServer\Contracts\MCPServiceInterface registerResourceTemplate(string $name, string $uriTemplate, callable $readCallback, array $metadata = [], ?callable $listCallback = null, array $completeCallbacks = [])
 * @method static \WizardingCode\MCPServer\Contracts\MCPServiceInterface registerTool(string $name, callable $callback, array $inputSchema = [], ?string $description = null)
 * @method static \WizardingCode\MCPServer\Contracts\MCPServiceInterface registerPrompt(string $name, callable $callback, array $argsSchema = [], ?string $description = null)
 *
 * @see \WizardingCode\MCPServer\Contracts\MCPServiceInterface
 */
class MCP extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mcp';
    }
}