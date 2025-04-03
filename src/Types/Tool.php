<?php

namespace WizardingCode\MCPServer\Types;

/**
 * Represents a tool in the MCP protocol
 */
class Tool
{
    /**
     * Create a new tool instance
     *
     * @param string $name The name of the tool
     * @param string|null $description Optional description of the tool
     * @param array $inputSchema JSON Schema object defining the expected parameters
     * @param array $additionalProperties Additional properties to include
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $inputSchema = ['type' => 'object'],
        public array $additionalProperties = []
    ) {
    }

    /**
     * Convert the tool to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $tool = [
            'name' => $this->name,
            'inputSchema' => $this->inputSchema,
        ];

        if ($this->description !== null) {
            $tool['description'] = $this->description;
        }

        return array_merge($tool, $this->additionalProperties);
    }
}