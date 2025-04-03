<?php

use Illuminate\Support\Facades\Route;
use WizardingCode\MCPServer\Http\Controllers\MCPController;

// MCP endpoint that handles all JSON-RPC requests and notifications
Route::post('/', [MCPController::class, 'handle'])
    ->name('mcp.handle');

// SSE endpoint for Server-Sent Events (optional for future implementation)
Route::get('/sse', function () {
    return response()->make('Not implemented yet', 501);
})->name('mcp.sse');