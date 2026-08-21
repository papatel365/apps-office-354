<?php

namespace App\Core\Traits;

use App\Modules\System\Models\AuditLog;
use App\Modules\System\Models\Tenant;
use Illuminate\Database\Eloquent\Model;

trait HasAuditLog
{
    protected static bool $auditEnabled = true;

    protected static function bootHasAuditLog(): void
    {
        // Audit on create
        static::created(function (Model $model) {
            if (static::$auditEnabled && static::shouldAudit()) {
                static::logAudit('created', $model, null, $model->getAttributes());
            }
        });

        // Audit on update
        static::updated(function (Model $model) {
            if (static::$auditEnabled && static::shouldAudit()) {
                $changes = $model->getChanges();
                if (!empty($changes)) {
                    static::logAudit('updated', $model, $model->getOriginal(), $changes);
                }
            }
        });

        // Audit on delete
        static::deleted(function (Model $model) {
            if (static::$auditEnabled && static::shouldAudit()) {
                $action = $model->forceDeleting ?? false ? 'force_deleted' : 'deleted';
                static::logAudit($action, $model, $model->getOriginal(), null);
            }
        });

        // Audit on restore (only if model uses SoftDeletes)
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function (Model $model) {
                if (static::$auditEnabled && static::shouldAudit()) {
                    static::logAudit('restored', $model, null, $model->getAttributes());
                }
            });
        }
    }

    protected static function shouldAudit(): bool
    {
        // Skip for models without tenant_id (shared tables)
        return true;
    }

    protected static function logAudit(
        string $action,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        $tenantId = null;

        // Get tenant_id from model
        if (!empty($model->tenant_id)) {
            // Verify tenant exists before using it
            // This prevents FK violations when tenant_id is stale/invalid
            $tenantExists = Tenant::where('id', $model->tenant_id)->exists();
            if ($tenantExists) {
                $tenantId = $model->tenant_id;
            }
            // If tenant doesn't exist, $tenantId remains null
        } elseif (function_exists('current_tenant_id')) {
            $tenantId = current_tenant_id();
        }

        // Skip audit logging entirely if no valid tenant
        if (!$tenantId) {
            return;
        }

        $user = auth()->user();

        AuditLog::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'tenant_id' => $tenantId,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'user_id' => $user?->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'request_id' => request()->header('X-Request-ID'),
            'created_at' => now(),
        ]);
    }

    public function disableAudit(): void
    {
        static::$auditEnabled = false;
    }

    public function enableAudit(): void
    {
        static::$auditEnabled = true;
    }

    public static function withoutAudit(callable $callback): mixed
    {
        $previousState = static::$auditEnabled;
        static::$auditEnabled = false;

        try {
            return $callback();
        } finally {
            static::$auditEnabled = $previousState;
        }
    }

    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }
}
