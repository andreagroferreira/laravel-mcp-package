<?php

namespace WizardingCode\MCPServer\Types;

/**
 * Represents a resource in the MCP protocol
 */
class Resource
{
    /**
     * Create a new resource instance
     *
     * @param string $uri The URI of the resource
     * @param string $name A human-readable name for the resource
     * @param string|null $description Optional description of the resource
     * @param string|null $mimeType Optional MIME type of the resource
     * @param array $additionalProperties Additional properties to include
     */
    public function __construct(
        public string $uri,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public array $additionalProperties = []
    ) {
    }

    /**
     * Convert the resource to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $resource = [
            'uri' => $this->uri,
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $resource['description'] = $this->description;
        }

        if ($this->mimeType !== null) {
            $resource['mimeType'] = $this->mimeType;
        }

        return array_merge($resource, $this->additionalProperties);
    }
}