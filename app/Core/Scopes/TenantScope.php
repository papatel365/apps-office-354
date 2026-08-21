<?php

namespace App\Core\Scopes;

use App\Services\TenantService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply tenant scope to queries.
     *
     * IMPORTANT: This scope is bypassed for:
     * - Developers (is_developer = true) - can see ALL tenant data
     * - Users accessing shared tables
     *
     * Developers can see all data across all tenants for:
     * - System administration
     * - Debugging and troubleshooting
     * - Cross-tenant reporting
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip for shared tables
        if ($this->isSharedTable($model)) {
            return;
        }

        // Get tenant ID from service
        $tenantId = $this->getCurrentTenantId();

        if ($tenantId) {
            $builder->where($model->getTable() . '.tenant_id', $tenantId);
        }
    }

    protected function getCurrentTenantId(): ?int
    {
        // Prevent infinite recursion
        static $resolving = false;
        if ($resolving) {
            return null;
        }

        try {
            $resolving = true;

            $user = auth()->user();

            // ============================================================
            // DEVELOPER BYPASS - Developers see all data across all tenants
            // ============================================================
            if ($user && $user->is_developer) {
                return null; // null = no scope applied = see all data
            }
            // ============================================================

            try {
                $tenantService = app(TenantService::class);
                return $tenantService->getCurrentTenantId();
            } catch (\Throwable $e) {
                return null;
            }
        } catch (\Throwable $e) {
            return null;
        } finally {
            $resolving = false;
        }
    }

    protected function isSharedTable(Model $model): bool
    {
        $sharedTables = [
            'countries',
            'currencies',
            'languages',
            'failed_jobs',
            'migrations',
            'password_reset_tokens',
            'personal_access_tokens',
        ];

        return in_array($model->getTable(), $sharedTables);
    }

    public function extend(Builder $builder): void
    {
        // Add query macro to remove tenant scope
        $builder->macro('withoutTenant', function (Builder $builder) {
            return $builder->withoutGlobalScope(static::class);
        });

        // Add query macro to scope to specific tenant
        $builder->macro('forTenant', function (Builder $builder, int $tenantId) {
            return $builder->withoutGlobalScope(static::class)
                         ->where('tenant_id', $tenantId);
        });
    }
}
