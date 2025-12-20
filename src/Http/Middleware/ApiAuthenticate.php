<?php

namespace Inovector\Mixpost\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inovector\Mixpost\Models\ApiToken;
use Inovector\Mixpost\Models\ApiLog;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$abilities)
    {
        $startTime = microtime(true);

        // Get token from header
        $bearerToken = $request->bearerToken();
        
        if (!$bearerToken) {
            return $this->unauthorized('Missing API token');
        }

        // Find and validate token
        $token = ApiToken::findByToken($bearerToken);
        
        if (!$token) {
            return $this->unauthorized('Invalid or expired API token');
        }

        // Check abilities if specified
        if (!empty($abilities) && !$token->canAny($abilities)) {
            return $this->forbidden('Insufficient permissions');
        }

        // Mark token as used
        $token->markUsed();

        // Store token in request for later use
        $request->attributes->set('api_token', $token);
        $request->attributes->set('api_start_time', $startTime);

        $response = $next($request);

        // Log the request
        $endTime = microtime(true);
        $responseTime = (int)(($endTime - $startTime) * 1000);

        ApiLog::log(
            $token->id,
            $request->method(),
            $request->path(),
            $response->getStatusCode(),
            $responseTime
        );

        return $response;
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Unauthorized',
            'message' => $message,
        ], 401);
    }

    /**
     * Return forbidden response
     */
    protected function forbidden(string $message): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'error' => 'Forbidden',
            'message' => $message,
        ], 403);
    }
}
