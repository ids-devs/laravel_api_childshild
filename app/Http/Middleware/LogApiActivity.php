<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs all authenticated API requests for audit trail.
 * Redacts sensitive fields (password, phone, token).
 */
class LogApiActivity
{
    private array $sensitiveFields = ['password', 'token', 'phone_number', 'phone_hash'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->user()) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    private function logRequest(Request $request, Response $response): void
    {
        $body = $request->except($this->sensitiveFields);

        Log::channel('api_audit')->info('API Request', [
            'user_id'    => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'method'     => $request->method(),
            'path'       => $request->path(),
            'status'     => $response->getStatusCode(),
            'ip'         => $request->ip(),
            'body'       => $body,
        ]);
    }
}
