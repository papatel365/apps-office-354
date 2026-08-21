<?php

namespace App\Helpers;

if (!function_exists('current_tenant')) {
    /**
     * Get the current tenant.
     */
    function current_tenant(): ?\App\Modules\System\Models\Tenant
    {
        return app('tenant')->getCurrentTenant();
    }
}

if (!function_exists('current_tenant_id')) {
    /**
     * Get the current tenant ID.
     */
    function current_tenant_id(): ?int
    {
        return current_tenant()?->id;
    }
}

if (!function_exists('tenancy')) {
    /**
     * Get the tenant manager instance.
     */
    function tenancy(): \App\Services\TenantService
    {
        return app(\App\Services\TenantService::class);
    }
}

if (!function_exists('tenant_user')) {
    /**
     * Get the current authenticated user.
     */
    function tenant_user(): ?\App\Modules\System\Models\User
    {
        return auth()->user();
    }
}

if (!function_exists('is_super_admin')) {
    /**
     * Check if current user is super admin.
     */
    function is_super_admin(): bool
    {
        return tenant_user()?->isSuperAdmin() ?? false;
    }
}

if (!function_exists('can_access')) {
    /**
     * Check if current user has permission.
     */
    function can_access(string $permission): bool
    {
        return tenant_user()?->can($permission) ?? false;
    }
}
