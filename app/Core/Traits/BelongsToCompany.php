<?php

namespace App\Core\Traits;

use App\Core\Scopes\CompanyScope;
use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Auto-add company_id when creating
        static::creating(function (Model $model) {
            $user = auth()->user();

            if ($user) {
                // Set company_id based on user role
                if (empty($model->company_id)) {
                    if ($user->is_developer || $user->is_pusat_admin) {
                        // Developer/Pusat can assign any company explicitly
                    } else {
                        $model->company_id = $user->company_id;
                    }
                }

                // Try to set tenant_id from company if not already set
                if ($model->company_id && $model->tenant_id === null) {
                    try {
                        $company = \Illuminate\Support\Facades\DB::table('companies')
                            ->where('id', $model->company_id)
                            ->value('tenant_id');
                        if ($company) {
                            $model->tenant_id = $company;
                        }
                    } catch (\Exception $e) {
                        // Ignore if company table doesn't have tenant_id
                    }
                }
            }
        });

        // Add global company scope
        static::addGlobalScope(new CompanyScope());
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function belongsToCompany(int $companyId): bool
    {
        return $this->company_id === $companyId;
    }

    public function scopeWithoutCompanyScope($query)
    {
        return $query->withoutGlobalScope(CompanyScope::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->withoutGlobalScope(CompanyScope::class)
                     ->where('company_id', $companyId);
    }
}
