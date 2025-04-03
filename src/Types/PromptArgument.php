<?php

namespace WizardingCode\MCPServer\Types;

/**
 * Represents an argument for a prompt in the MCP protocol
 */
class PromptArgument
{
    /**
     * Create a new prompt argument instance
     *
     * @param string $name The name of the argument
     * @param string|null $description Optional description of the argument
     * @param bool $required Whether this argument is required
     * @param array $additionalProperties Additional properties to include
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $required = false,
        public array $additionalProperties = []
    ) {
    }

    /**
     * Convert the prompt argument to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $argument = [
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $argument['description'] = $this->description;
        }

        if ($this->required) {
            $argument['required'] = true;
        }

        return array_merge($argument, $this->additionalProperties);
    }
}