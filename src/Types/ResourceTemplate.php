<?php

namespace WizardingCode\MCPServer\Types;

/**
 * Represents a resource template in the MCP protocol
 */
class ResourceTemplate
{
    /**
     * Create a new resource template instance
     *
     * @param string $uriTemplate A URI template (RFC 6570) for constructing resource URIs
     * @param string $name A human-readable name for the type of resource
     * @param string|null $description Optional description of the template
     * @param string|null $mimeType Optional MIME type for resources matching this template
     * @param array $additionalProperties Additional properties to include
     */
    public function __construct(
        public string $uriTemplate,
        public string $name,
        public ?string $description = null,
        public ?string $mimeType = null,
        public array $additionalProperties = []
    ) {
    }

    /**
     * Convert the resource template to an array representation
     *
     * @return array
     */
    public function toArray(): array
    {
        $template = [
            'uriTemplate' => $this->uriTemplate,
            'name' => $this->name,
        ];

        if ($this->description !== null) {
            $template['description'] = $this->description;
        }

        if ($this->mimeType !== null) {
            $template['mimeType'] = $this->mimeType;
        }

        return array_merge($template, $this->additionalProperties);
    }
}