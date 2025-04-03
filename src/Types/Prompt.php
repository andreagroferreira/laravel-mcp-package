<?php

namespace WizardingCode\MCPServer\Types;

/**
 * Represents a prompt in the MCP protocol
 */
class Prompt
{
    /**
     * Create a new prompt instance
     *
     * @param string $name The name of the prompt
     * @param string|null $description Optional description of the prompt
     * @param array $arguments Optional list of arguments the prompt accepts
     * @param array $additionalProperties Additional properties to include
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $arguments = [],
        public array $additionalProperties = []
    ) {
    }

    /**
     * Convert the prompt to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $prompt = [
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $prompt['description'] = $this->description;
        }

        if (!empty($this->arguments)) {
            $prompt['arguments'] = $this->arguments;
        }

        return array_merge($prompt, $this->additionalProperties);
    }
}