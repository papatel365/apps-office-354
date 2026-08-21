<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Module;
use App\Services\Permission\UserPermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Module Access Middleware
 *
 * This middleware checks:
 * 1. If the user/company has access to the module (company entitlement)
 * 2. If the user has been granted access to the module (user permission)
 *
 * Uses UserPermissionService as SINGLE SOURCE OF TRUTH for user permissions.
 *
 * Usage:
 * Route::middleware(['module.access:hrd_expert'])->group(...)
 */
class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Developer always has access to all modules
        if ($user->is_developer) {
            return $next($request);
        }

        // Get company
        $company = $user->company;

        if (!$company) {
            // For pusat admin or users without company
            if ($user->is_pusat_admin) {
                return $next($request);
            }

            return $this->denyAccess($request, $moduleCode, 'Company not found');
        }

        // Check if module exists
        $module = Module::where('code', $moduleCode)->first();

        if (!$module) {
            return $this->denyAccess($request, $moduleCode, 'Modul tidak ditemukan');
        }

        // Check if module is active
        if (!$module->is_active) {
            return $this->denyAccess($request, $moduleCode, 'Modul sedang tidak aktif');
        }

        // Check if COMPANY has access to this module (entitlement)
        if (!$company->hasModuleAccess($moduleCode)) {
            return $this->denyAccess($request, $moduleCode, "Modul belum aktif untuk perusahaan ini");
        }

        // Check if USER has been granted access
        // Directors and Owners automatically have access
        if ($user->is_director || $user->is_owner || $user->is_company_admin) {
            return $next($request);
        }

        // Use the unified permission service
        $permService = UserPermissionService::forUser($user);

        if (!$permService->canView($moduleCode)) {
            return $this->denyAccess($request, $moduleCode, "Anda tidak memiliki izin untuk mengakses modul ini");
        }

        // Store module info in request for later use
        $request->merge(['active_module' => $module]);

        return $next($request);
    }

    /**
     * Handle denied access
     */
    protected function denyAccess(Request $request, string $moduleCode, string $reason): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => $reason,
                'module_code' => $moduleCode,
                'action' => 'permission_denied',
            ], 403);
        }

        // Redirect to beranda with error
        return redirect()->route('dashboard')
            ->with('error', $reason);
    }
}
