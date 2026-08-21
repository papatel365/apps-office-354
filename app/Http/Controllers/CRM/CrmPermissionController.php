<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmUserPermissionV2;
use App\Models\Company;
use App\Models\HRD\EmployeeProfile;
use App\Modules\System\Models\User;
use App\Services\SidebarMenuConfig;
use App\Services\Permission\UserPermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * CRM Permission Controller - UNIFIED ARCHITECTURE
 *
 * SINGLE SOURCE OF TRUTH:
 * - SATU permission object per module
 * - SATU render function
 * - SATU save flow
 */
class CrmPermissionController extends Controller
{
    /**
     * Display the permission management page.
     *
     * DATA SOURCE: Uses EmployeeProfile as source to ensure consistency
     * with Data Karyawan page. This ensures Hak Akses always shows the
     * same users as Data Karyawan.
     */
    public function index(): View
    {
        $user = auth()->user();

        if (!$this->canManagePermissions($user)) {
            abort(403, 'Anda tidak memiliki akses untuk mengatur perizinan CRM.');
        }

        $companyId = $user->company_id;

        // Get all employees with their linked user accounts
        // Using EmployeeProfile as source to match Data Karyawan page
        $employees = EmployeeProfile::where('company_id', $companyId)
            ->with(['user'])
            ->orderBy('is_active', 'desc') // Active employees first
            ->orderByRaw("FIELD(employment_type, 'permanent', 'contract', 'probation', 'part_time', 'intern', 'outsource')")
            ->orderBy('full_name')
            ->get()
            ->map(function ($employee) {
                // Only process employees that have a linked user account
                if (!$employee->user) {
                    return null;
                }

                $u = $employee->user;
                $permService = UserPermissionService::forUser($u);
                $u->module_permissions = $permService->getPermissions();
                $u->is_superadmin = $this->isSuperAdmin($u);
                $u->is_protected = $this->isProtectedRole($u);

                // Store employee data for display
                $u->employee_id = $employee->id;
                $u->employee_full_name = $employee->full_name;
                $u->employee_is_active = $employee->is_active;
                $u->employee_type = $employee->employment_type;

                return $u;
            })
            ->filter(); // Remove null values (employees without user accounts)

        // Re-index the collection
        $users = $employees->values();

        // Get visible permission modules structure for UI
        $permissionModules = SidebarMenuConfig::getVisiblePermissionModules();

        // Get companies for developer mode
        $companies = [];
        if ($user->is_developer) {
            $companies = Company::where('is_active', true)->orderBy('name')->get();
        }

        return view('crm.crm-permissions.index', compact(
            'users',
            'permissionModules',
            'companies',
            'companyId'
        ));
    }

    /**
     * Get permissions for a specific user (AJAX).
     *
     * Returns UNIFIED permission structure:
     * {
     *   user: {...},
     *   is_superadmin: bool,
     *   is_protected: bool,
     *   can_edit: bool,
     *   permissions: {
     *     projects: { sidebar: true, scope_own: true, scope_global: false, can_view: true, can_create: true, can_update: true, can_delete: true },
     *     tasks: {...},
     *     ...
     *   },
     *   structure: [...]
     * }
     */
    public function getUserPermissions(int $userId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$this->canManagePermissions($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengatur perizinan.',
                ], 403);
            }

            $targetUser = User::findOrFail($userId);

            if (!$user->is_developer && $targetUser->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak berada di perusahaan yang sama.',
                ], 403);
            }

            $isProtected = $this->isProtectedRole($targetUser);
            $isSuperadmin = $this->isSuperAdmin($targetUser);

            // ============================================================
            // LOAD: Get permissions from UserPermissionService
            // ============================================================
            $permService = UserPermissionService::forUser($targetUser);
            $modulePermissions = $permService->getPermissions();
            $managedModules = SidebarMenuConfig::getManagedModules();

            // ============================================================
            // BUILD UNIFIED PERMISSION OBJECT
            // ============================================================
            $permissions = [];

            foreach ($managedModules as $module) {
                // Get existing permission or default
                $perm = $modulePermissions[$module] ?? [
                    'scope_own' => false,
                    'scope_global' => false,
                    'can_view' => false,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                ];

                // Sidebar access = can_view OR scope_own OR scope_global
                $sidebar = $perm['can_view'] || $perm['scope_own'] || $perm['scope_global'];

                $permissions[$module] = [
                    'sidebar' => $sidebar,
                    'scope_own' => $perm['scope_own'],
                    'scope_global' => $perm['scope_global'],
                    'can_view' => $perm['can_view'],
                    'can_create' => $perm['can_create'],
                    'can_update' => $perm['can_update'],
                    'can_delete' => $perm['can_delete'],
                ];
            }

            // Superadmin gets all permissions
            if ($isSuperadmin) {
                foreach ($managedModules as $module) {
                    $permissions[$module] = [
                        'sidebar' => true,
                        'scope_own' => true,
                        'scope_global' => true,
                        'can_view' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                    ];
                }
            }

            // Get visible permission structure for UI
            $permissionStructure = SidebarMenuConfig::getVisiblePermissionModules();

            Log::info('[PERM LOAD] User ' . $targetUser->id . ' - permissions:', $permissions);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $targetUser->id,
                        'name' => $targetUser->name,
                        'email' => $targetUser->email,
                        'role' => $targetUser->company_role_label,
                        'role_key' => $targetUser->company_role ?? $targetUser->user_type ?? null,
                    ],
                    'is_superadmin' => $isSuperadmin,
                    'is_protected' => $isProtected,
                    'can_edit' => !$isProtected && $this->canEditUserPermissions($user, $targetUser),
                    'permissions' => $permissions,
                    'structure' => $permissionStructure,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('CrmPermissionController Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update permissions for a specific user.
     *
     * Receives UNIFIED permission object:
     * {
     *   permissions: {
     *     projects: { sidebar: true, scope_own: true, ... },
     *     tasks: {...},
     *     ...
     *   }
     * }
     *
     * Saves to crm_user_permissions_v2 in SINGLE TRANSACTION.
     */
    public function updateUserPermissions(Request $request, int $userId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$this->canManagePermissions($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengatur perizinan.',
                ], 403);
            }

            $targetUser = User::findOrFail($userId);

            if (!$user->is_developer && $targetUser->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak berada di perusahaan yang sama.',
                ], 403);
            }

            if (!$this->canEditUserPermissions($user, $targetUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak dapat mengubah izin pengguna ini.',
                ], 403);
            }

            if ($this->isProtectedRole($targetUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mengubah izin untuk role ini.',
                ], 403);
            }

            // ============================================================
            // VALIDATE UNIFIED PAYLOAD
            // ============================================================
            $request->validate([
                'permissions' => 'nullable|array',
            ]);

            $permissions = $request->input('permissions', []);

            Log::info('[PERM SAVE] User ' . $targetUser->id . ' - received:', $permissions);

            // ============================================================
            // BUILD FINAL PERMISSIONS FROM UNIFIED OBJECT
            // ============================================================
            $finalPermissions = [];
            $managedModules = SidebarMenuConfig::getManagedModules();

            foreach ($permissions as $module => $perm) {
                // Only process managed modules
                if (!in_array($module, $managedModules)) {
                    continue;
                }

                // Build permission record
                // sidebar checkbox affects can_view
                $sidebar = !empty($perm['sidebar']);
                $canView = $sidebar || !empty($perm['can_view']);

                $finalPermissions[$module] = [
                    'scope_own' => !empty($perm['scope_own']),
                    'scope_global' => !empty($perm['scope_global']),
                    'can_view' => $canView,
                    'can_create' => !empty($perm['can_create']),
                    'can_update' => !empty($perm['can_update']),
                    'can_delete' => !empty($perm['can_delete']),
                ];
            }

            Log::info('[PERM SAVE] User ' . $targetUser->id . ' - final:', $finalPermissions);

            // ============================================================
            // SAVE IN SINGLE TRANSACTION
            // ============================================================
            $permService = UserPermissionService::forUser($targetUser);

            DB::transaction(function () use ($permService, $finalPermissions) {
                $permService->savePermissions($finalPermissions);
            });

            // ============================================================
            // VERIFY
            // ============================================================
            $savedRecords = CrmUserPermissionV2::where('user_id', $targetUser->id)->get();

            Log::info('[PERM SAVE] User ' . $targetUser->id . ' - verified:', $savedRecords->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Hak Akses berhasil disimpan.',
                'data' => [
                    'saved_count' => $savedRecords->count(),
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('CrmPermissionController Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset permissions to default.
     */
    public function resetUserPermissions(int $userId): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$this->canManagePermissions($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk mengatur perizinan.',
                ], 403);
            }

            $targetUser = User::findOrFail($userId);

            if (!$user->is_developer && $targetUser->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak berada di perusahaan yang sama.',
                ], 403);
            }

            if ($this->isProtectedRole($targetUser)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat mereset izin untuk role ini.',
                ], 403);
            }

            $permService = UserPermissionService::forUser($targetUser);
            $permService->initializeDefaults();

            Log::info('[PERM RESET] User ' . $targetUser->id);

            return response()->json([
                'success' => true,
                'message' => 'Hak Akses berhasil direset ke default.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('CrmPermissionController Reset Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =====================================================
    // HELPER METHODS
    // =====================================================

    protected function canManagePermissions($user): bool
    {
        if (!$user) {
            return false;
        }

        // Super Admin (Developer/Owner/Director) can always manage permissions
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        // Check if user has the 'hak_akses' module permission
        // This matches the sidebar permission key from SidebarMenuConfig
        $permService = UserPermissionService::forUser($user);
        if ($permService->can('hak_akses')) {
            return true;
        }

        return false;
    }

    protected function isSuperAdmin($user): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->is_developer || $user->company_role === 'developer') {
            return true;
        }
        return false;
    }

    protected function isProtectedRole(User $user): bool
    {
        $role = $user->company_role ?? $user->user_type ?? null;
        if ($role === 'developer' || $user->is_developer) {
            return true;
        }
        if ($role === 'owner') {
            return true;
        }
        return false;
    }

    /**
     * Check if current user can edit target user's permissions.
     *
     * AUTHORIZATION LOGIC:
     * - Developer can always edit (full access)
     * - Owner can edit anyone except owner and developer
     * - Director can edit anyone except owner, developer, director
     * - User with 'hak_akses' permission can edit anyone except
     *   owner, developer, director (based on their own permission scope)
     */
    protected function canEditUserPermissions($currentUser, User $targetUser): bool
    {
        // Developer always can edit
        if ($currentUser->is_developer || $currentUser->company_role === 'developer') {
            return true;
        }

        $currentRole = $currentUser->company_role ?? $currentUser->user_type ?? null;
        $targetRole = $targetUser->company_role ?? $targetUser->user_type ?? null;

        // Owner can edit anyone except owner and developer
        if ($currentRole === 'owner') {
            if ($targetRole === 'owner' || $targetUser->is_developer) {
                return false;
            }
            return true;
        }

        // Director can edit anyone except owner, developer, director
        if ($currentRole === 'director') {
            if (in_array($targetRole, ['owner', 'developer', 'director'])) {
                return false;
            }
            return true;
        }

        // ============================================================
        // NON-PRIVILEGED ROLES (Admin, Manager, Staff)
        // Check if user has 'hak_akses' permission
        // ============================================================
        $permService = UserPermissionService::forUser($currentUser);
        if ($permService->can('hak_akses')) {
            // User has hak_akses permission
            // Can edit anyone except owner, developer, director, and themselves
            if ($currentUser->id === $targetUser->id) {
                return false; // Cannot edit own permissions
            }
            if (in_array($targetRole, ['owner', 'developer', 'director'])) {
                return false; // Cannot edit privileged roles
            }
            return true;
        }

        // No permission to edit
        return false;
    }
}
