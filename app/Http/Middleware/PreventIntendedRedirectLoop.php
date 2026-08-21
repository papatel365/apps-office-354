<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent API/AJAX routes from being stored as intended redirect URLs.
 *
 * This middleware ensures that AJAX polling requests (like notification polling)
 * don't get stored as the "intended URL" in the session, which could cause
 * users to be redirected to API endpoints after login instead of the dashboard.
 *
 * Routes that match these patterns will have their URLs excluded from the
 * intended redirect logic:
 * - /notifications/* (AJAX notification endpoints)
 * - /api/* (API routes)
 * - /ajax/* (AJAX routes)
 * - Routes that respond with JSON (Accept: application/json)
 */
class PreventIntendedRedirectLoop
{
    /**
     * API/AJAX route patterns that should never be stored as intended URLs.
     * These are simple string patterns, not regex.
     */
    protected array $excludedPatterns = [
        'notifications/unread-count',
        'notifications/dropdown',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is an AJAX/JSON request - these should never be stored as intended
        if ($this->shouldExcludeFromIntended($request)) {
            // Store the previous URL before any redirects happen
            // This prevents the current URL from being stored as intended
            $this->ensureValidIntendedUrl($request);
        }

        return $next($request);
    }

    /**
     * Determine if the request should be excluded from intended URL storage.
     */
    protected function shouldExcludeFromIntended(Request $request): bool
    {
        // If request expects JSON, it's definitely an API/AJAX request
        if ($request->expectsJson()) {
            return true;
        }

        // Check if request is an AJAX request
        if ($this->isAjaxRequest($request)) {
            return true;
        }

        // Check route patterns using simple string matching (not regex)
        $path = $request->path();

        foreach ($this->excludedPatterns as $pattern) {
            if ($path === $pattern) {
                return true;
            }
        }

        // Also check if path starts with api/ or ajax/
        if (str_starts_with($path, 'api/') || str_starts_with($path, 'ajax/')) {
            return true;
        }

        return false;
    }

    /**
     * Check if request is an AJAX request.
     */
    protected function isAjaxRequest(Request $request): bool
    {
        // Check X-Requested-With header
        if ($request->isXmlHttpRequest()) {
            return true;
        }

        // Check Accept header for JSON
        $accept = $request->header('Accept', '');
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return false;
    }

    /**
     * Ensure the session doesn't store an invalid intended URL.
     */
    protected function ensureValidIntendedUrl(Request $request): void
    {
        // If session has an intended URL that looks like an API/AJAX endpoint,
        // clear it to prevent redirect loops
        $intendedUrl = Session::get('url.intended');

        if ($intendedUrl && $this->isApiOrAjaxUrl($intendedUrl)) {
            Session::forget('url.intended');
        }
    }

    /**
     * Check if a URL is an API or AJAX endpoint.
     */
    protected function isApiOrAjaxUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        // Extract path from URL
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $path = trim($path, '/');

        // Check common API/AJAX patterns using simple string matching
        $invalidPatterns = [
            'notifications/unread-count',
            'notifications/dropdown',
        ];

        foreach ($invalidPatterns as $pattern) {
            if ($path === $pattern || str_starts_with($path, $pattern)) {
                return true;
            }
        }

        // Check if path starts with api/ or ajax/
        if (str_starts_with($path, 'api/') || str_starts_with($path, 'ajax/')) {
            return true;
        }

        return false;
    }
}
