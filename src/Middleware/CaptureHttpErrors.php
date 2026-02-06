<?php

namespace Codprez\IssueReporter\Middleware;

use Closure;
use Codprez\IssueReporter\Facades\Issue;
use Throwable;

class CaptureHttpErrors
{
    public function handle($request, Closure $next)
    {
        try {
            $response = $next($request);

            if ($response->status() >= 500) {
                Issue::capture("HTTP 5xx error: " . $response->status(), [
                    'status' => $response->status(),
                ], 'error');
            }

            return $response;
        } catch (Throwable $e) {
            // Exception will be handled by the Exception Handler hook,
            // but we can add extra request context here if needed.
            throw $e;
        }
    }
}
