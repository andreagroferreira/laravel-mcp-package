<?php

namespace WizardingCode\MCPServer\Exceptions;

use Exception;
use WizardingCode\MCPServer\Types\ErrorCode;

class MCPException extends Exception
{
    /**
     * The error data
     *
     * @var mixed
     */
    protected $data;

    /**
     * Create a new MCPException instance.
     *
     * @param int $code Error code
     * @param string $message Error message
     * @param mixed $data Additional error data
     */
    public function __construct(int $code, string $message, $data = null)
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }

    /**
     * Get the error data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Create a method not found exception
     *
     * @param string $method The method that was not found
     * @return static
     */
    public static function methodNotFound(string $method): self
    {
        return new static(
            ErrorCode::METHOD_NOT_FOUND,
            "Method not found: {$method}"
        );
    }

    /**
     * Create an invalid parameters exception
     *
     * @param string $message The error message
     * @param mixed $data Additional error data
     * @return static
     */
    public static function invalidParams(string $message, $data = null): self
    {
        return new static(
            ErrorCode::INVALID_PARAMS,
            $message,
            $data
        );
    }

    /**
     * Create an internal error exception
     *
     * @param string $message The error message
     * @param mixed $data Additional error data
     * @return static
     */
    public static function internalError(string $message, $data = null): self
    {
        return new static(
            ErrorCode::INTERNAL_ERROR,
            $message,
            $data
        );
    }

    /**
     * Create a parse error exception
     *
     * @param string $message The error message
     * @param mixed $data Additional error data
     * @return static
     */
    public static function parseError(string $message, $data = null): self
    {
        return new static(
            ErrorCode::PARSE_ERROR,
            $message,
            $data
        );
    }

    /**
     * Create an invalid request exception
     *
     * @param string $message The error message
     * @param mixed $data Additional error data
     * @return static
     */
    public static function invalidRequest(string $message, $data = null): self
    {
        return new static(
            ErrorCode::INVALID_REQUEST,
            $message,
            $data
        );
    }
}