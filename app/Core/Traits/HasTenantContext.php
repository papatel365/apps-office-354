<?php

namespace App\Core\Traits;

use App\Services\TenantService;

trait HasTenantContext
{
    /**
     * Get the current tenant ID from multiple sources.
     */
    protected function resolveTenantId(): ?int
    {
        $tenantService = app(TenantService::class);
        $user = $this->user();

        // Try in order: service, user, company
        $tenantId = $tenantService->getCurrentTenantId()
            ?? $user->tenant_id
            ?? $this->getTenantIdFromCompany($user->company_id);

        return $tenantId;
    }

    /**
     * Get tenant ID from other users in the same company.
     */
    protected function getTenantIdFromCompany(?int $companyId): ?int
    {
        if (!$companyId) {
            return null;
        }

        return \App\Modules\System\Models\User::where('company_id', $companyId)
            ->whereNotNull('tenant_id')
            ->value('tenant_id');
    }

    /**
     * Set tenant_id in data array.
     */
    protected function setTenantId(array &$data): void
    {
        $data['tenant_id'] = $this->resolveTenantId();
    }
}
