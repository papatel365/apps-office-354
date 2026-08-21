<?php

namespace App\Services\Dashboard;

use App\Models\HRD\EmployeeProfile;
use App\Modules\System\Models\User;
use App\Modules\System\Models\ActivityLog;
use App\Services\Permission\UserPermissionService;

/**
 * Dashboard Data Service
 *
 * NO PERMISSION CHECKS FOR ACCESS.
 * Dashboard is always accessible to all logged-in users.
 *
 * DATA FILTERING:
 * - scope_global = true → All company data
 * - scope_own = true → Own data only
 * - Neither → Empty results (0)
 */
class DashboardDataService
{
    protected $user;
    protected $companyId;
    protected $tenantId;
    protected $permService;

    public function __construct()
    {
        $this->user = auth()->user();
        $this->companyId = $this->user->company_id ?? null;
        $this->tenantId = $this->user->tenant_id ?? null;
        $this->permService = $this->user
            ? UserPermissionService::forUser($this->user)
            : new UserPermissionService(null);
    }

    /**
     * Get all dashboard data based on SCOPE permissions
     */
    public function getAllData(): array
    {
        return [
            'stats' => $this->getStats(),
            'recentEmployees' => $this->getRecentEmployees(),
            'recentActivities' => $this->getRecentActivities(),
            'attendanceStats' => $this->getAttendanceStats(),
            'permissions' => $this->getUserPermissions(),
        ];
    }

    /**
     * Check if user has GLOBAL scope for a module
     */
    protected function hasGlobalScope(string $module): bool
    {
        return $this->permService->isGlobalScope($module);
    }

    /**
     * Check if user has OWN scope for a module
     */
    protected function hasOwnScope(string $module): bool
    {
        return $this->permService->isOwnScope($module);
    }

    /**
     * Check if user has ANY scope (global or own) for a module
     */
    protected function hasAnyScope(string $module): bool
    {
        return $this->hasGlobalScope($module) || $this->hasOwnScope($module);
    }

    /**
     * Get all stats based on SCOPE permissions
     * Dashboard is always accessible - stats show 0 if no scope
     */
    public function getStats(): array
    {
        $stats = [
            // HRD
            'total_employees' => 0,
            'active_employees' => 0,
            'total_users' => 0,
            'active_users' => 0,
            'show_hrd' => false,

            // Attendance
            'attendance_present' => 0,
            'attendance_late' => 0,
            'attendance_absent' => 0,
        ];

        // HRD stats - show company-wide (not scope filtered)
        $stats = array_merge($stats, $this->getHRDStats());

        // User stats - show company-wide (not scope filtered)
        $stats = array_merge($stats, $this->getUserStats());

        return $stats;
    }

    /**
     * Get HRD-related stats (company-wide, not scope filtered)
     */
    protected function getHRDStats(): array
    {
        if (!$this->companyId) {
            return [
                'total_employees' => 0,
                'active_employees' => 0,
                'show_hrd' => false,
            ];
        }

        return [
            'total_employees' => EmployeeProfile::where('company_id', $this->companyId)->count(),
            'active_employees' => EmployeeProfile::where('company_id', $this->companyId)->where('is_active', true)->count(),
            'show_hrd' => true,
        ];
    }

    /**
     * Get user-related stats (company-wide, not scope filtered)
     */
    protected function getUserStats(): array
    {
        return [
            'total_users' => User::when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))->count(),
            'active_users' => User::when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))->active()->count(),
        ];
    }
    /**
     * Get recent employees (company-wide, not scope filtered)
     */
    public function getRecentEmployees($limit = 5): \Illuminate\Support\Collection
    {
        if (!$this->companyId) {
            return collect();
        }

        return EmployeeProfile::with('user')
            ->where('company_id', $this->companyId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent activities (company-wide)
     */
    public function getRecentActivities($limit = 10): \Illuminate\Support\Collection
    {
        $companyUserIds = User::when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))->pluck('id')->toArray();

        return ActivityLog::with('user')
            ->where('tenant_id', $this->tenantId)
            ->whereIn('user_id', $companyUserIds)
            ->where('subject_type', '!=', \App\Models\Asset::class)
            ->where('subject_type', '!=', \App\Models\AssetCategory::class)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Roles that are excluded from attendance calculations.
     */
    protected array $excludedRoles = ['director', 'owner'];

    /**
     * Get attendance stats (company-wide, excluding directors/owners)
     */
    public function getAttendanceStats(): array
    {
        if (!$this->companyId) {
            return [
                'present' => 0,
                'late' => 0,
                'absent' => 0,
            ];
        }

        // Get eligible employee IDs (excluding directors/owners based on company_role)
        $eligibleEmployeeIds = EmployeeProfile::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereNotNull('user_id')
            ->whereHas('user', function ($q) {
                $q->where(function ($subQ) {
                    $subQ->whereNull('company_role')
                         ->orWhereNotIn('company_role', $this->excludedRoles);
                });
            })
            ->pluck('id')
            ->toArray();

        return [
            'present' => 0,
            'late' => 0,
            'absent' => count($eligibleEmployeeIds),
        ];
    }

    /**
     * Get user's permission list
     */
    public function getUserPermissions(): array
    {
        $accessibleModules = $this->permService->getAccessibleModules();
        $keys = [];

        foreach ($accessibleModules as $module) {
            $keys[] = 'sidebar.' . $module;
        }

        return $keys;
    }

    /**
     * Check if user has any module access (for sidebar display)
     * This does NOT affect Dashboard access
     */
    public function hasAnyModuleAccess(): bool
    {
        return $this->permService->isSuperAdmin()
            || $this->permService->isGlobalScope('staff_dashboard')
            || $this->permService->isGlobalScope('employees')
            || $this->permService->isGlobalScope('attendances')
            || $this->permService->isGlobalScope('staff_reports');
    }
}
