<?php

namespace WizardingCode\MCPServer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use WizardingCode\MCPServer\Contracts\MCPServiceInterface;
use WizardingCode\MCPServer\Exceptions\MCPException;
use WizardingCode\MCPServer\Types\ErrorCode;
use WizardingCode\MCPServer\Types\MessageType;

class MCPController extends Controller
{
    /**
     * The MCP service instance
     *
     * @var MCPServiceInterface
     */
    protected MCPServiceInterface $mcpService;

    /**
     * Create a new MCPController instance
     *
     * @param MCPServiceInterface $mcpService
     */
    public function __construct(MCPServiceInterface $mcpService)
    {
        $this->mcpService = $mcpService;
        
        // Apply auth middleware if enabled
        if (config('mcp.auth.enabled', true)) {
            $this->middleware('auth:' . config('mcp.auth.guard', 'api'));
        }
    }

    /**
     * Handle an incoming MCP message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        // Validate JSON-RPC message
        $message = $request->all();
        
        if (!isset($message['jsonrpc']) || $message['jsonrpc'] !== '2.0') {
            return $this->errorResponse(
                null,
                ErrorCode::INVALID_REQUEST,
                'Invalid JSON-RPC version'
            );
        }

        if (!isset($message['method']) || !is_string($message['method'])) {
            return $this->errorResponse(
                $message['id'] ?? null,
                ErrorCode::INVALID_REQUEST,
                'Missing or invalid method'
            );
        }

        // Determine message type
        $method = $message['method'];
        $params = $message['params'] ?? [];
        $id = $message['id'] ?? null;
        
        $messageType = isset($message['id'])
            ? MessageType::REQUEST
            : MessageType::NOTIFICATION;

        try {
            // Process the message
            $result = $this->mcpService->processMessage($messageType, $method, $params, $id, $request);
            
            // For notifications, return an empty 204 response
            if ($messageType === MessageType::NOTIFICATION) {
                return response()->json(null, 204);
            }
            
            // For requests, return the result
            return response()->json([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result,
            ]);
        } catch (MCPException $e) {
            return $this->errorResponse(
                $id,
                $e->getCode(),
                $e->getMessage(),
                $e->getData()
            );
        } catch (\Exception $e) {
            // Log the unexpected error
            Log::error('MCP error: ' . $e->getMessage(), [
                'exception' => $e,
                'message' => $message,
            ]);
            
            return $this->errorResponse(
                $id,
                ErrorCode::INTERNAL_ERROR,
                'Internal server error: ' . $e->getMessage()
            );
        }
    }

    /**
     * Create a JSON-RPC error response
     *
     * @param mixed $id Request ID
     * @param int $code Error code
     * @param string $message Error message
     * @param mixed $data Additional error data
     * @return JsonResponse
     */
    protected function errorResponse($id, int $code, string $message, $data = null): JsonResponse
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($data !== null) {
            $response['error']['data'] = $data;
        }

        return response()->json($response, 200);
    }
}