<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * This middleware handles TENANT authorization - ensuring users belong to a company.
     *
     * IMPORTANT: Developer bypass rules:
     * - Developers are allowed through WITHOUT requiring company_id
     * - Developers can access tenant routes for administration purposes
     * - Developers have full system access across all tenants
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // ============================================================
        // DEVELOPER BYPASS - Developers have full system access
        // ============================================================
        // Developers can access ANY route without being blocked by tenant auth.
        // They bypass the company_id requirement and can view all tenant data
        // for administration and debugging purposes.
        if ($user->is_developer) {
            Log::debug('[TenantMiddleware] Developer bypass - allowing full access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'route' => $request->getRequestUri(),
            ]);
            return $next($request);
        }
        // ============================================================

        // For non-developer (tenant) users, ensure they belong to a company
        if (!$user->company_id) {
            abort(403, 'Access denied. You must belong to a company to access this area.');
        }

        return $next($request);
    }
}
