<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     * Only use this for third-party webhooks or API endpoints that are accessed by
     * external systems that cannot use CSRF tokens.
     *
     * SECURITY NOTE: Disabling CSRF protection should be avoided whenever possible.
     * For browser-accessible endpoints, CSRF protection should ALWAYS be enabled.
     * For API endpoints, use proper API authentication like tokens or OAuth instead.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Example for third-party webhook endpoints:
        // 'api/webhooks/*',
    ];
}
