<?php

namespace App\Core\Traits;

use App\Core\Scopes\TenantScope;
use App\Modules\System\Models\Tenant;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Auto-add tenant_id when creating
        static::creating(function (Model $model) {
            if (empty($model->tenant_id)) {
                $tenantId = null;

                // Priority 1: Try current_tenant helper first
                if (function_exists('current_tenant_id')) {
                    $tenantId = current_tenant_id();
                }

                // Priority 2: Fallback to auth user tenant
                if (empty($tenantId) && function_exists('auth') && auth()->check()) {
                    $tenantId = auth()->user()?->tenant_id;
                }

                // Priority 3: Fallback to company tenant (via company_name match)
                if (empty($tenantId) && !empty($model->company_id)) {
                    $company = Company::find($model->company_id);
                    if ($company) {
                        $tenant = Tenant::where('name', $company->name)->first();
                        if ($tenant) {
                            $tenantId = $tenant->id;
                        }
                    }
                }

                // Priority 4: Try to get tenant_id from parent model's tenant_id
                // This handles cases like ProjectTag -> Project where the child model
                // doesn't have company_id but has a foreign key to a model that has tenant_id
                if (empty($tenantId)) {
                    $tenantId = self::resolveTenantIdFromParentModel($model);
                }

                if ($tenantId) {
                    $model->tenant_id = $tenantId;
                }
            }
        });

        // Add global tenant scope
        static::addGlobalScope(new TenantScope());
    }

    /**
     * Try to resolve tenant_id from a parent model's tenant_id.
     * Looks for foreign keys that point to models with tenant_id.
     */
    protected static function resolveTenantIdFromParentModel(Model $model): ?int
    {
        // Define known parent relationships: foreign_key => [parent_model_class, parent_fk_field]
        // The child model may have fields like project_id, task_id, etc.
        // We need to find the parent model and check if it has tenant_id
        $parentRelations = [
            'project_id' => [\App\Models\Project::class],
            'task_id' => [\App\Models\Task::class],
            'asset_id' => [\App\Models\Asset::class],
            'asset_category_id' => [\App\Models\AssetCategory::class],
            'user_id' => [\App\Modules\System\Models\User::class],
            'company_id' => [Company::class],
        ];

        foreach ($parentRelations as $fkField => $parentClasses) {
            if (!empty($model->{$fkField})) {
                foreach ($parentClasses as $parentClass) {
                    $parent = $parentClass::find($model->{$fkField});
                    if ($parent && !empty($parent->tenant_id)) {
                        return (int) $parent->tenant_id;
                    }
                }
            }
        }

        return null;
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function belongsToTenant(int $tenantId): bool
    {
        return $this->tenant_id === $tenantId;
    }

    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
                     ->where('tenant_id', $tenantId);
    }
}
