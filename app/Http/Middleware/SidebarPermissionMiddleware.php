<?php

namespace App\Http\Middleware;

use App\Services\Permission\UserPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SidebarPermissionMiddleware
{
    /**
     * Handle an incoming request.
     * Uses UserPermissionService as SINGLE SOURCE OF TRUTH.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Use the unified permission service
        $permService = UserPermissionService::forUser($user);

        if (!$permService->canAccessSidebar($module)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke halaman ini.'
                ], 403);
            }
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
