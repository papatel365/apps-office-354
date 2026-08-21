<?php

namespace App\Services;

use App\Modules\System\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TenantService
{
    protected ?Tenant $currentTenant = null;
    protected bool $enabled = true;

    public function setCurrentTenant(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function getCurrentTenant(): ?Tenant
    {
        return $this->currentTenant;
    }

    public function getCurrentTenantId(): ?int
    {
        return $this->currentTenant?->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function runForTenant(Tenant $tenant, callable $callback): mixed
    {
        $previousTenant = $this->currentTenant;

        $this->setCurrentTenant($tenant);
        $this->enable();

        try {
            return $callback();
        } finally {
            $this->currentTenant = $previousTenant;
        }
    }

    public function getTenantById(int $id): ?Tenant
    {
        return Cache::remember(
            "tenant.{$id}",
            config('nexus.tenant.cache_ttl', 3600),
            fn () => Tenant::find($id)
        );
    }

    public function getTenantByDomain(string $domain): ?Tenant
    {
        return Cache::remember(
            "tenant.domain.{$domain}",
            config('nexus.tenant.cache_ttl', 3600),
            fn () => Tenant::where('domain', $domain)->active()->first()
        );
    }

    public function getTenantBySlug(string $slug): ?Tenant
    {
        return Cache::remember(
            "tenant.slug.{$slug}",
            config('nexus.tenant.cache_ttl', 3600),
            fn () => Tenant::where('slug', $slug)->active()->first()
        );
    }

    public function clearTenantCache(int $tenantId): void
    {
        Cache::forget("tenant.{$tenantId}");

        $tenant = Tenant::find($tenantId);
        if ($tenant) {
            Cache::forget("tenant.slug.{$tenant->slug}");
            Cache::forget("tenant.domain.{$tenant->domain}");
        }
    }
}
