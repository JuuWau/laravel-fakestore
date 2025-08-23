<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class IntegrationMiddleware
{
    protected $maxAttempts = 10;
    protected $decaySeconds = 60;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $requestId = (string) Str::uuid();
        $request->headers->set('X-Request-Id', $requestId);

        $clientId = $request->hasHeader('X-Client-Id');
        if (!$clientId) {
            return response()->json(['error' => 'X-Client-Id header missing'], 400);
        }

        $key = "rate_limit:{$clientId}";

        $attempts = Cache::get($key, 0);

        if ($attempts >= $this->maxAttempts) {
            return response()->json(['message' => 'Too many requests'], 429);
        }

        Cache::put($key, $attempts + 1, $this->decaySeconds);

        $start = microtime(true);

        Log::info('Request started', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'client_id' => $request->header('X-Client-Id')
        ]);

        $response = $next($request);

        $duration = microtime(true) - $start;

        Log::info('Request finished', [
            'request_id' => $requestId,
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration * 1000
        ]);

        return $response;
    }
}
