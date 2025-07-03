<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExcludeCsrfMiddleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * Only use this for third-party webhooks or API endpoints that are accessed by
     * external systems that cannot use CSRF tokens.
     *
     * @var array<string>
     */
    protected $except = [
        // Use very specific patterns for webhook endpoints
        // Do NOT exclude entire API namespace
    ];

    /**
     * Handle an incoming request.
     * Note: UNUSED - this is properly handled by the VerifyCsrfToken middleware
     * This class has been kept for documentation purposes only.
     *
     * @param  Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // This function doesn't exclude CSRF checks
        // To exclude paths from CSRF, you should modify the 'except' array in VerifyCsrfToken middleware
        return $next($request);
    }

    /**
     * Determine if the request has a URI that should pass through CSRF verification.
     * This method no longer has an effect as ExcludeCsrfMiddleware is not used.
     *
     * @param  Request  $request
     * @return bool
     */
    protected function shouldPassThrough(Request $request): bool
    {
        // For security reasons, this method now always returns false
        // If you need to exclude paths from CSRF protection:
        // 1. Add them to the $except array in VerifyCsrfToken middleware
        // 2. Use API authentication for API endpoints (tokens, sanctum, etc.)
        // 3. Never disable CSRF for browser-accessible routes
        return false;
    }
}
