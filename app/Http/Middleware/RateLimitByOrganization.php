<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies different rate limits depending on organization type.
 * - admin/government: 1000 req/min
 * - ong/unicef: 300 req/min
 * - clinic: 100 req/min
 */
class RateLimitByOrganization
{
    public function __construct(private RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user  = $request->user();
        $limit = $this->getLimit($user?->organization_type ?? 'clinic');
        $key   = "api_rate:{$user?->id}:{$request->path()}";

        if ($this->limiter->tooManyAttempts($key, $limit)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => $this->limiter->availableIn($key),
            ], 429);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }

    private function getLimit(?string $type): int
    {
        return match($type) {
            'admin', 'government', 'unicef' => 1000,
            'ong'                           => 300,
            default                         => 100,
        };
    }
}
