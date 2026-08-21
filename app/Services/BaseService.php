<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

abstract class BaseService
{
    /**
     * Get authenticated user.
     */
    protected function user()
    {
        return auth()->user();
    }

    /**
     * Get current tenant ID.
     */
    protected function tenantId(): ?int
    {
        return $this->user()?->tenant_id;
    }

    /**
     * Get cache TTL in seconds.
     */
    protected function cacheTTL(int $minutes = 60): int
    {
        return $minutes * 60;
    }

    /**
     * Cache remember.
     */
    protected function remember(string $key, callable $callback, int $ttl = 60): mixed
    {
        return Cache::remember($key, $this->cacheTTL($ttl), $callback);
    }

    /**
     * Cache remember forever.
     */
    protected function rememberForever(string $key, callable $callback): mixed
    {
        return Cache::rememberForever($key, $callback);
    }

    /**
     * Clear cache by key prefix.
     */
    protected function clearCache(string $prefix): void
    {
        Cache::flush();
    }
}
