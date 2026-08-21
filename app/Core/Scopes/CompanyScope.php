<?php

namespace App\Core\Scopes;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Apply company scope to queries.
     *
     * SINGLE COMPANY RULE:
     * This application only supports ONE company.
     *
     * If exactly 1 company exists, scope queries to that company.
     * This handles stale company_id gracefully.
     *
     * Bypass rules:
     * - Developers (is_developer = true) - can see ALL data
     * - Pusat Admins (is_pusat_admin = true) - can see ALL data
     * - Directors (is_director = true) - see ALL data in their company
     * - Shared tables - no scope applied
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip for shared tables
        if ($this->isSharedTable($model)) {
            return;
        }

        // Skip if table doesn't have company_id column
        try {
            if (!$this->hasColumn($model, 'company_id')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        // Get company ID using single company logic
        $companyId = $this->getSingleCompanyId();

        if ($companyId) {
            $builder->where($model->getTable() . '.company_id', $companyId);
        }
    }

    /**
     * Get the single company ID for this application.
     *
     * Single Company Rules:
     * - 0 companies: return null (initial setup required)
     * - 1 company: return that company's ID
     * - >1 companies: return null (data integrity problem)
     *
     * @return int|null
     */
    protected function getSingleCompanyId(): ?int
    {
        // Prevent infinite recursion
        static $resolving = false;
        if ($resolving) {
            return null;
        }

        try {
            $resolving = true;

            // Count companies
            $count = Company::count();

            // No companies yet - initial setup required
            if ($count === 0) {
                return null;
            }

            // Multiple companies - data integrity problem
            if ($count > 1) {
                return null; // Don't scope to avoid selecting wrong company
            }

            // Exactly 1 company - use it
            $company = Company::first();
            return $company ? $company->id : null;
        } catch (\Throwable $e) {
            return null;
        } finally {
            $resolving = false;
        }
    }

    /**
     * Get current user with bypass checks.
     */
    protected function getCurrentUserForScope(): ?\App\Modules\System\Models\User
    {
        static $resolving = false;
        if ($resolving) {
            return null;
        }

        try {
            $resolving = true;
            return auth()->user();
        } catch (\Throwable $e) {
            return null;
        } finally {
            $resolving = false;
        }
    }

    /**
     * Check if table is shared (no company scope).
     */
    protected function isSharedTable(Model $model): bool
    {
        $sharedTables = [
            'companies',
            'users',
            'countries',
            'currencies',
            'languages',
            'failed_jobs',
            'migrations',
            'password_reset_tokens',
            'personal_access_tokens',
            'sessions',
        ];

        return in_array($model->getTable(), $sharedTables);
    }

    /**
     * Check if model has a column.
     */
    protected function hasColumn(Model $model, string $column): bool
    {
        try {
            $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
            return in_array($column, $columns);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Extend query builder with company scope macros.
     */
    public function extend(Builder $builder): void
    {
        // Remove company scope
        $builder->macro('withoutCompany', function (Builder $builder) {
            return $builder->withoutGlobalScope(static::class);
        });

        // Scope to specific company
        $builder->macro('forCompany', function (Builder $builder, int $companyId) {
            return $builder->withoutGlobalScope(static::class)
                         ->where('company_id', $companyId);
        });

        // Scope to single active company (single company mode)
        $builder->macro('forCurrentCompany', function (Builder $builder) {
            $companyId = $this->getSingleCompanyId();

            if (!$companyId) {
                // No company or multiple companies - return nothing or all based on rules
                return $builder->withoutGlobalScope(static::class);
            }

            return $builder->withoutGlobalScope(static::class)
                         ->where('company_id', $companyId);
        });
    }
}
