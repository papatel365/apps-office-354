<?php

namespace App\Http\Middleware;

use App\Services\Permission\UserPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSidebarPermission
{
    /**
     * Handle an incoming request.
     * Checks if user has permission to access a sidebar menu item.
     *
     * Uses UserPermissionService as SINGLE SOURCE OF TRUTH.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = auth()->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            abort(401, 'Unauthorized');
        }

        // Use the unified permission service
        $permService = UserPermissionService::forUser($user);

        if (!$permService->canAccessSidebar($module)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Anda tidak memiliki akses ke menu ini.'
                ], 403);
            }
            abort(403, 'Anda tidak memiliki akses ke menu ini.');
        }

        return $next($request);
    }
}
