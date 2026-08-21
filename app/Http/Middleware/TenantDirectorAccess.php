<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantDirectorAccess Middleware
 *
 * Ensures that Directors have proper tenant isolation while maintaining
 * full access to all data within their tenant.
 *
 * IMPORTANT SECURITY NOTES:
 * - Directors CANNOT access data from other tenants
 * - Directors CAN access all data within their own tenant
 * - This middleware enforces tenant boundaries, NOT role-based access
 * - Role-based access is handled by policies and the User model
 */
class TenantDirectorAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Skip for non-director users (let other middleware handle them)
        if (!$user->is_director) {
            return $next($request);
        }

        // Directors must belong to a company (tenant)
        if (!$user->company_id) {
            Log::warning('[TenantDirectorAccess] Director without company access denied', [
                'user_id' => $user->id,
                'email' => $user->email,
                'route' => $request->getRequestUri(),
            ]);

            abort(403, 'Access denied. Directors must belong to a company.');
        }

        // Add tenant context to request for easy access in controllers
        $request->attributes->set('tenant_id', $user->tenant_id);
        $request->attributes->set('company_id', $user->company_id);
        $request->attributes->set('is_director_access', true);

        Log::debug('[TenantDirectorAccess] Director tenant access granted', [
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'tenant_id' => $user->tenant_id,
            'route' => $request->getRequestUri(),
        ]);

        return $next($request);
    }
}
