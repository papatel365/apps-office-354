<?php

namespace App\Core\Middleware;

use App\Modules\System\Models\Tenant;
use App\Services\TenantService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected TenantService $tenantService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Skip for console commands (except in tenant context)
        if (app()->runningInConsole() && !$request->has('tenant')) {
            return $next($request);
        }

        // CRITICAL: Skip developer routes completely
        // Use Laravel's path matching
        if ($request->is('developer/*') || $request->is('developer')) {
            return $next($request);
        }

        // Skip for developers
        $user = $request->user();
        if ($user && $user->is_developer) {
            return $next($request);
        }

        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant not found',
                    'error' => 'TENANT_NOT_FOUND'
                ], 404);
            }

            return redirect()->route('login');
        }

        // Check if tenant is active
        if (!$tenant->isActive()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant subscription is not active',
                    'error' => 'TENANT_INACTIVE'
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Subscription inactive');
        }

        // Set tenant in container
        $this->tenantService->setCurrentTenant($tenant);

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        // Skip developers
        try {
            $user = $request->user();
            if ($user && $user->is_developer) {
                return null;
            }
        } catch (\Throwable $e) {
            // User model loading failed
        }

        // Try to resolve tenant
        try {
            $tenant = $this->tenantService->getCurrentTenant();
            return $tenant;
        } catch (\Throwable $e) {
            // Tenant resolution failed
            return null;
        }
    }
}
